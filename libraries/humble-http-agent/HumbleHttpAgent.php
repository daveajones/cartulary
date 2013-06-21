<?php
/**
 * Humble HTTP Agent
 * 
 * This class is designed to take advantage of parallel HTTP requests
 * offered by PHP's PECL HTTP extension or the curl_multi_* functions. 
 * For environments which do not have these options, it reverts to standard sequential 
 * requests (using file_get_contents())
 * 
 * @version 0.9.5
 * @date 2011-05-23
 * @see http://php.net/HttpRequestPool
 * @author Keyvan Minoukadeh
 * @copyright 2011 Keyvan Minoukadeh
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPL v3
 */

class HumbleHttpAgent
{
	const METHOD_REQUEST_POOL = 1;
	const METHOD_CURL_MULTI = 2;
	const METHOD_FILE_GET_CONTENTS = 4;
	
	protected $requests = array();
	protected $redirectQueue = array();
	protected $requestOptions;
	protected $maxParallelRequests = 5;
	protected $cache = null; //TODO
	protected $httpContext;
	protected $minimiseMemoryUse = false; //TODO
	protected $debug = false;
	protected $method;
	protected $cookieJar;
	public $rewriteHashbangFragment = true; // see http://code.google.com/web/ajaxcrawling/docs/specification.html
	public $maxRedirects = 5;
	
	//TODO: prevent certain file/mime types
	//TODO: set max file size
	//TODO: normalise headers
	
	function __construct($requestOptions=null, $method=null) {
		// set the request method
		if (in_array($method, array(1,2,4))) {
			$this->method = $method;
		} else {
			if (class_exists('HttpRequestPool')) {
				$this->method = self::METHOD_REQUEST_POOL;
			} elseif (function_exists('curl_multi_init')) {
				$this->method = self::METHOD_CURL_MULTI;
			} else {
				$this->method = self::METHOD_FILE_GET_CONTENTS;
			}
		}
		if ($this->method == self::METHOD_CURL_MULTI) {
			require_once(dirname(__FILE__).'/RollingCurl.php');
		}
		// create cookie jar
		$this->cookieJar = new CookieJar();
		// set request options (redirect must be 0)
		$this->requestOptions = array(
			'timeout' => 10,
			'redirect' => 0 // we handle redirects manually so we can rewrite the new hashbang URLs that are creeping up over the web
			// TODO: test onprogress?
		);
		if (is_array($requestOptions)) {
			$this->requestOptions = array_merge($this->requestOptions, $requestOptions);
		}
		$this->httpContext = array(
			'http' => array(
				'ignore_errors' => true,
				'timeout' => $this->requestOptions['timeout'],
				'max_redirects' => $this->requestOptions['redirect'],
				'header' => "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/4.0)\r\n".
							"Accept: */*\r\n"
				)
			);
	}
	
	protected function debug($msg) {
		if ($this->debug) {
			$mem = round(memory_get_usage()/1024, 2);
			$memPeak = round(memory_get_peak_usage()/1024, 2);
			echo '* ',$msg;
			echo ' - mem used: ',$mem," (peak: $memPeak)\n";	
			ob_flush();
			flush();
		}
	}
	
	public function rewriteHashbangFragment($url) {
		// return $url if there's no '#!'
		if (strpos($url, '#!') === false) return $url;
		// split $url and rewrite
		$iri = new IRI($url);
		$fragment = substr($iri->ifragment, 1); // strip '!'
		$iri->fragment = null;
		if (isset($iri->iquery)) {
			parse_str($iri->iquery, $query);
		} else {
			$query = array();
		}
		$query['_escaped_fragment_'] = (string)$fragment;
		$iri->query = str_replace('%2F', '/', http_build_query($query)); // needed for some sites
		return $iri->uri;
	}
	
	public function removeFragment($url) {
		$pos = strpos($url, '#');
		if ($pos === false) {
			return $url;
		} else {
			return substr($url, 0, $pos);
		}
	}	
	
	public function enableDebug($bool=true) {
		$this->debug = (bool)$bool;
	}
	
	public function minimiseMemoryUse($bool = true) {
		$this->minimiseMemoryUse = $bool;
	}
	
	public function setMaxParallelRequests($max) {
		$this->maxParallelRequests = $max;
	}
	
	public function validateUrl($url) {
		$url = filter_var($url, FILTER_SANITIZE_URL);
		$test = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
		// deal with bug http://bugs.php.net/51192 (present in PHP 5.2.13 and PHP 5.3.2)
		if ($test === false) {
			$test = filter_var(strtr($url, '-', '_'), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
		}
		if ($test !== false && $test !== null && preg_match('!^https?://!', $url)) {
			return $url;
		} else {
			return false;
		}
	}
	
	/**
	 * Set cache object.
	 * The cache object passed should implement Zend_Cache_Backend_Interface
	 * @param Zend_Cache_Backend_Interface
	 */
	/* all disk caching temporily disabled - needs work
	 public function useCache($cache) {
		$this->cache = $cache;
	}	
	
	public function isCached($url) {
		if (!isset($this->cache)) return false;
		return ($this->cache->test(md5($url)) !== false);
	}
	
	public function getCached($url) {
		$cached = $this->cache->load(md5($url));
		$cached['fromCache'] = true;
		return $cached;
	}
	
	public function cache($url) {
		if (isset($this->cache) && !isset($this->requests[$url]['fromCache']) && isset($this->requests[$url]['body'])) {
			$this->debug("Saving to cache ($url)");
			$res = $this->cache->save($this->requests[$url], md5($url));
			//$res = @file_put_contents($this->cacheFolder.'/'.md5($url).'.txt', serialize($this->requests[$url]));
			return ($res !== false);
		}
		return false;
	}
	
	public function cacheAll() {
		if (isset($this->cache)) {
			foreach (array_keys($this->requests) as $url) {
				$this->cache($url);
			}
			return true;
		}
		return false;
	}
	*/
	
	public function fetchAll(array $urls) {
		$this->fetchAllOnce($urls, $isRedirect=false);
		$redirects = 0;
		while (!empty($this->redirectQueue) && ++$redirects <= $this->maxRedirects) {
			$this->debug("Following redirects #$redirects...");
			$this->fetchAllOnce($this->redirectQueue, $isRedirect=true);
		}
	}
	
	// fetch all URLs without following redirects
	public function fetchAllOnce(array $urls, $isRedirect=false) {
		if (!$isRedirect) $urls = array_unique($urls);
		if (empty($urls)) return;
		
		//////////////////////////////////////////////////////
		// parallel (HttpRequestPool)
		if ($this->method == self::METHOD_REQUEST_POOL) {
			$this->debug('Starting parallel fetch (HttpRequestPool)');
			try {
				while (count($urls) > 0) {
					$this->debug('Processing set of '.min($this->maxParallelRequests, count($urls)));
					$subset = array_splice($urls, 0, $this->maxParallelRequests);
					$pool = new HttpRequestPool();
					foreach ($subset as $orig => $url) {
						if (!$isRedirect) $orig = $url;
						unset($this->redirectQueue[$orig]);
						$this->debug("...$url");
						if (!$isRedirect && isset($this->requests[$url])) {
							$this->debug("......in memory");
						/*
						} elseif ($this->isCached($url)) {
							$this->debug("......is cached");
							if (!$this->minimiseMemoryUse) {
								$this->requests[$url] = $this->getCached($url);
							}
						*/
						} else {
							$this->debug("......adding to pool");
							$req_url = ($this->rewriteHashbangFragment) ? $this->rewriteHashbangFragment($url) : $url;
							$req_url = $this->removeFragment($req_url);
							$httpRequest = new HttpRequest($req_url, HttpRequest::METH_GET, $this->requestOptions);
							// send cookies, if we have any
							if ($cookies = $this->cookieJar->getMatchingCookies($req_url)) {
								$this->debug("......sending cookies: $cookies");
								$httpRequest->addHeaders(array('Cookie' => $cookies));
							}
							$this->requests[$orig] = array('headers'=>null, 'body'=>null, 'httpRequest'=>$httpRequest);
							$this->requests[$orig]['original_url'] = $orig;
							$pool->attach($httpRequest);
						}
					}
					// did we get anything into the pool?
					if (count($pool) > 0) {
						$this->debug('Sending request...');
						try {
							$pool->send();
						} catch (HttpRequestPoolException $e) {
							// do nothing
						}
						$this->debug('Received responses');
						foreach($subset as $orig => $url) {
							if (!$isRedirect) $orig = $url;
							//if (!isset($this->requests[$url]['fromCache'])) {
								$request = $this->requests[$orig]['httpRequest'];
								//$this->requests[$orig]['headers'] = $this->headersToString($request->getResponseHeader());
								// getResponseHeader() doesn't return status line, so, for consistency...
								$this->requests[$orig]['headers'] = substr($request->getRawResponseMessage(), 0, $request->getResponseInfo('header_size'));
								$this->requests[$orig]['body'] = $request->getResponseBody();
								$this->requests[$orig]['effective_url'] = $request->getResponseInfo('effective_url');
								$this->requests[$orig]['status_code'] = $status_code = $request->getResponseCode();
								// is redirect?
								if ((in_array($status_code, array(300, 301, 302, 303, 307)) || $status_code > 307 && $status_code < 400) && $request->getResponseHeader('location')) {
									$redirectURL = $request->getResponseHeader('location');
									if (!preg_match('!^https?://!i', $redirectURL)) {
										$redirectURL = SimplePie_Misc::absolutize_url($redirectURL, $url);
									}
									if ($this->validateURL($redirectURL)) {
										$this->debug('Redirect detected. Valid URL: '.$redirectURL);
										// store any cookies
										$cookies = $request->getResponseHeader('set-cookie');
										if ($cookies && !is_array($cookies)) $cookies = array($cookies);
										if ($cookies) $this->cookieJar->storeCookies($url, $cookies);
										$this->redirectQueue[$orig] = $redirectURL;
									} else {
										$this->debug('Redirect detected. Invalid URL: '.$redirectURL);
									}
								}
								//die($url.' -multi- '.$request->getResponseInfo('effective_url'));
								$pool->detach($request);
								unset($this->requests[$orig]['httpRequest'], $request);
								/*
								if ($this->minimiseMemoryUse) {
									if ($this->cache($url)) {
										unset($this->requests[$url]);
									}
								}
								*/
							//}
						}
					}
				}
			} catch (HttpException $e) {
				$this->debug($e);
				return false;
			}
		}
		
		//////////////////////////////////////////////////////////
		// parallel (curl_multi_*)
		elseif ($this->method == self::METHOD_CURL_MULTI) {
			$this->debug('Starting parallel fetch (curl_multi_*)');
			while (count($urls) > 0) {
				$this->debug('Processing set of '.min($this->maxParallelRequests, count($urls)));
				$subset = array_splice($urls, 0, $this->maxParallelRequests);
				$pool = new RollingCurl(array($this, 'handleCurlResponse'));
				$pool->window_size = count($subset);		
				
				foreach ($subset as $orig => $url) {
					if (!$isRedirect) $orig = $url;
					unset($this->redirectQueue[$orig]);
					$this->debug("...$url");
					if (!$isRedirect && isset($this->requests[$url])) {
						$this->debug("......in memory");
					/*
					} elseif ($this->isCached($url)) {
						$this->debug("......is cached");
						if (!$this->minimiseMemoryUse) {
							$this->requests[$url] = $this->getCached($url);
						}
					*/
					} else {
						$this->debug("......adding to pool");
						$req_url = ($this->rewriteHashbangFragment) ? $this->rewriteHashbangFragment($url) : $url;
						$req_url = $this->removeFragment($req_url);
						$headers = array();
						// send cookies, if we have any
						if ($cookies = $this->cookieJar->getMatchingCookies($req_url)) {
							$this->debug("......sending cookies: $cookies");
							$headers[] = 'Cookie: '.$cookies;
						}
						$httpRequest = new RollingCurlRequest($req_url, 'GET', null, $headers, array(
							CURLOPT_CONNECTTIMEOUT => $this->requestOptions['timeout'],
							CURLOPT_TIMEOUT => $this->requestOptions['timeout']
							));
						$httpRequest->set_original_url($orig);
						$this->requests[$orig] = array('headers'=>null, 'body'=>null, 'httpRequest'=>$httpRequest);
						$this->requests[$orig]['original_url'] = $orig; // TODO: is this needed anymore?
						$pool->add($httpRequest);
					}
				}
				// did we get anything into the pool?
				if (count($pool) > 0) {
					$this->debug('Sending request...');
					$pool->execute(); // this will call handleCurlResponse() and populate $this->requests[$orig]
					$this->debug('Received responses');
					foreach($subset as $orig => $url) {
						if (!$isRedirect) $orig = $url;
						// $this->requests[$orig]['headers']
						// $this->requests[$orig]['body']
						// $this->requests[$orig]['effective_url']
						$status_code = $this->requests[$orig]['status_code'];
						if ((in_array($status_code, array(300, 301, 302, 303, 307)) || $status_code > 307 && $status_code < 400) && isset($this->requests[$orig]['location'])) {
							$redirectURL = $this->requests[$orig]['location'];
							if (!preg_match('!^https?://!i', $redirectURL)) {
								$redirectURL = SimplePie_Misc::absolutize_url($redirectURL, $url);
							}
							if ($this->validateURL($redirectURL)) {
								$this->debug('Redirect detected. Valid URL: '.$redirectURL);
								// store any cookies
								$cookies = $this->cookieJar->extractCookies($this->requests[$orig]['headers']);
								if (!empty($cookies)) $this->cookieJar->storeCookies($url, $cookies);							
								$this->redirectQueue[$orig] = $redirectURL;
							} else {
								$this->debug('Redirect detected. Invalid URL: '.$redirectURL);
							}
						}
						// die($url.' -multi- '.$request->getResponseInfo('effective_url'));
						unset($this->requests[$orig]['httpRequest']);
					}
				}
			}
		}

		//////////////////////////////////////////////////////
		// sequential (file_get_contents)
		else {
			$this->debug('Starting sequential fetch (file_get_contents)');
			$this->debug('Processing set of '.count($urls));
			foreach ($urls as $orig => $url) {
				if (!$isRedirect) $orig = $url;
				unset($this->redirectQueue[$orig]);
				$this->debug("...$url");
				if (!$isRedirect && isset($this->requests[$url])) {
					$this->debug("......in memory");
				/*
				} elseif ($this->isCached($url)) {
					$this->debug("......is cached");
					if (!$this->minimiseMemoryUse) {
						$this->requests[$url] = $this->getCached($url);
					}
				*/
				} else {
					$this->debug("Sending request for $url");
					$this->requests[$orig]['original_url'] = $orig;					
					$req_url = ($this->rewriteHashbangFragment) ? $this->rewriteHashbangFragment($url) : $url;
					$req_url = $this->removeFragment($req_url);
					// send cookies, if we have any
					$httpContext = $this->httpContext;
					if ($cookies = $this->cookieJar->getMatchingCookies($req_url)) {
						$this->debug("......sending cookies: $cookies");
						$httpContext['http']['header'] .= 'Cookie: '.$cookies."\r\n";
					}
					if (false !== ($html = @file_get_contents($req_url, false, stream_context_create($httpContext)))) {
						$this->debug('Received response');
						// get status code
						if (!isset($http_response_header[0]) || !preg_match('!^HTTP/\d+\.\d+\s+(\d+)!', trim($http_response_header[0]), $match)) {
							$this->debug('Error: no status code found');
							// TODO: handle error - no status code
						} else {
							$this->requests[$orig]['headers'] = $this->headersToString($http_response_header, false);
							$this->requests[$orig]['body'] = $html;
							$this->requests[$orig]['effective_url'] = $req_url;
							$this->requests[$orig]['status_code'] = $status_code = (int)$match[1];
							unset($match);
							// handle redirect
							if (preg_match('/^Location:(.*?)$/m', $this->requests[$orig]['headers'], $match)) {
								$this->requests[$orig]['location'] =  trim($match[1]);
							}
							if ((in_array($status_code, array(300, 301, 302, 303, 307)) || $status_code > 307 && $status_code < 400) && isset($this->requests[$orig]['location'])) {
								$redirectURL = $this->requests[$orig]['location'];
								if (!preg_match('!^https?://!i', $redirectURL)) {
									$redirectURL = SimplePie_Misc::absolutize_url($redirectURL, $url);
								}
								if ($this->validateURL($redirectURL)) {
									$this->debug('Redirect detected. Valid URL: '.$redirectURL);
									// store any cookies
									$cookies = $this->cookieJar->extractCookies($this->requests[$orig]['headers']);
									if (!empty($cookies)) $this->cookieJar->storeCookies($url, $cookies);
									$this->redirectQueue[$orig] = $redirectURL;
								} else {
									$this->debug('Redirect detected. Invalid URL: '.$redirectURL);
								}
							}
						}
					} else {
						$this->debug('Error retrieving URL');
						//print_r($req_url);
						//print_r($http_response_header);
						//print_r($html);
						
						// TODO: handle error - failed to retrieve URL
					}
				}
			}
		}
	}
	
	public function handleCurlResponse($response, $info, $request) {
		$orig = $request->url_original;
		$this->requests[$orig]['headers'] = substr($response, 0, $info['header_size']);
		$this->requests[$orig]['body'] = substr($response, $info['header_size']);
		$this->requests[$orig]['effective_url'] = $info['url'];
		$this->requests[$orig]['status_code'] = (int)$info['http_code'];
		if (preg_match('/^Location:(.*?)$/m', $this->requests[$orig]['headers'], $match)) {
			$this->requests[$orig]['location'] =  trim($match[1]);
		}
	}
	
	protected function headersToString(array $headers, $associative=true) {
		if (!$associative) {
			return implode("\n", $headers);
		} else {
			$str = '';
			foreach ($headers as $key => $val) {
				if (is_array($val)) {
					foreach ($val as $v) $str .= "$key: $v\n";
				} else {
					$str .= "$key: $val\n";
				}
			}
			return rtrim($str);
		}
	}
	
	public function get($url, $remove=false) {
		$url = "$url";
		if (isset($this->requests[$url]) && isset($this->requests[$url]['body'])) {
			$this->debug("URL already fetched - in memory ($url, effective: {$this->requests[$url]['effective_url']})");
			$response = $this->requests[$url];
		/*
		} elseif ($this->isCached($url)) {
			$this->debug("URL already fetched - in disk cache ($url)");
			$response = $this->getCached($url);
			$this->requests[$url] = $response;
		*/
		} else {
			$this->debug("Fetching URL ($url)");
			$this->fetchAll(array($url));
			if (isset($this->requests[$url]) && isset($this->requests[$url]['body'])) {
				$response = $this->requests[$url];
			} else {
				$this->debug("Request failed");
				$response = false;
			}
		}
		/*
		if ($this->minimiseMemoryUse && $response) {
			$this->cache($url);
			unset($this->requests[$url]);
		}
		*/
		if ($remove && $response) unset($this->requests[$url]);
		return $response;
	}
	
	public function parallelSupport() {
		return class_exists('HttpRequestPool') || function_exists('curl_multi_init');
	}
}
?>
