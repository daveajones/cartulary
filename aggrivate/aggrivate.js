//Requires
var mysql = require('mysql');
var request = require('request');
var fs = require('graceful-fs');
var ini = require('ini');
var Iconv = require('iconv').Iconv;
var crypto = require('crypto');

//Globals
var netcalls = 0;
var dbcalls = 0;
var dbcheck = 0;
var query = 0;
var checkall = false;
var checkone = false;
var checkdead = false;
var checkerror = false;
var ckoneurl = '';
var netwait = 60;
var feedcount = 0;
var force = false;

//Get command line args
process.argv.forEach((val, index, array) => {
    console.log(index + ": [" + val + "]");
    if (index >= 2 && val === "checkall") {
        console.log("Checking all feeds.");
        checkall = true;
    }

    if (index >= 2 && val === "checkdead") {
        console.log("Checking dead feeds.");
        checkall = true;
    }

    if (index >= 2 && val === "checkerror") {
        console.log("Checking high error feeds.");
        checkerror = true;
    }

    if (index >= 2 && val === "force") {
        console.log("Ignoring last-modified.");
        force = true;
    }

    if (!checkall && index >= 2 && val.indexOf('http') !== -1) {
        console.log("Checking feed: [" + val + "]");
        ckoneurl = val;
        checkone = true;
    }
});

//Get the database and table info
var config = ini.parse(fs.readFileSync('/opt/cartulary/conf/cartulary.conf', 'utf-8'));

//console.log(config.database);
loggit(3, "DEBUG: Aggrivate is runnning.");

//Get a connection to mysql
var connection = mysql.createConnection({
    host: config.database.dbhost,
    user: config.database.dbuser,
    password: config.database.dbpass,
    database: config.database.dbname
});
connection.connect(function (err) {
    if (err) {
        console.error('Error connecting to mysql: ' + err.stack);
        process.exit(1);
    }
});

//Timestamp for one month ago
var monthago = (Date.now() / 1000) - (28 * 86400);

//Assemble query
var query = 'SELECT id,title,url,lastmod,createdon,contenttype,contenthash FROM ' + config.tables.table_newsfeed + ' WHERE (errors < 100 OR lastupdate > ' + monthago + ' OR lastcheck = 0 OR lastmod = 0 OR content = "") AND dead=0 ORDER by lastcheck ASC';
if (checkall && checkdead) {
    query = 'SELECT id,title,url,lastmod,createdon,contenttype,contenthash FROM ' + config.tables.table_newsfeed + ' ORDER by lastcheck ASC';
}
if (checkall && !checkdead) {
    query = 'SELECT id,title,url,lastmod,createdon,contenttype,contenthash FROM ' + config.tables.table_newsfeed + ' WHERE dead=0 ORDER by lastcheck ASC';
}
if (!checkall && checkdead) {
    query = 'SELECT id,title,url,lastmod,createdon,contenttype,contenthash FROM ' + config.tables.table_newsfeed + ' WHERE dead=1 ORDER by lastcheck ASC';
}
if (checkerror) {
    query = 'SELECT id,title,url,lastmod,createdon,contenttype,contenthash FROM ' + config.tables.table_newsfeed + ' WHERE dead=0 AND ( errors > 100 || content = "") ORDER by lastcheck ASC';
}
if (checkone) {
    query = 'SELECT id,title,url,lastmod,createdon,contenttype,contenthash FROM ' + config.tables.table_newsfeed + ' WHERE url="' + ckoneurl + '"';
}

//Pull the feed list
dbcalls++;
connection.query(query, function (err, rows, fields) {
    //Bail on error
    if (err) throw err;

    //console.log(rows);
    if (rows.count < 1 && checkone) {
        console.log("Couldn't find feed: [" + ckoneurl + "] in the database.");
    }

    for (var row in rows) {
        var feed = rows[row];
        feedcount++;

        if (checkone) {
            console.log("Checking feed: [" + ckoneurl + "]");
        }

        //Ignore feeds that dont start with http scheme

        //Give the console what we're checking
        //console.log(rows[row].id + ' : ', rows[row].url);

        //Don't attempt to fetch feeds with non-fqdn urls
        if (feed.url.toLowerCase().indexOf('http') !== 0) {
            console.log("Error: Skipping non-fqdn feed url: [" + feed.url + "]");
            continue;
        }

        //Make the get request
        (function (f) {
            netcalls++;
            var lastmod = 0;
            var redirectCodes = [];
            var opt = {
                uri: f.url,
                followRedirect: function (resp) {
                    redirectCodes.push(resp.statusCode);
                    //console.log("  Redirect: ["+resp.statusCode+"]");
                    //resp.headers.location = resp.headers.location + '?fmt=xml';
                    //console.log(resp);
                    return true;
                },
                followAllRedirects: true,
                gzip: true,
                strictSSL: false,
                pool: false,
                encoding: null,
                timeout: (netwait - 5) * 1000,
                jar: true,
                removeRefererHeader: true,
                maxRedirects: 9,
                ecdhCurve: 'auto'
            };

            if (f.lastmod === 0) {
                lastmod = new Date((Date.now() - (86400 * 1000))).toUTCString();
            } else {
                lastmod = new Date(f.lastmod * 1000).toUTCString();
            }
            if (force || checkone || checkerror || f.contenttype == 'none' || f.contenttype == '') {
                //Set the lastmod to 1/1/1990 so we get new content for everything
                lastmod = new Date(631152000).toUTCString();
            }
            f.lastmodPretty = lastmod;
            opt.headers = {
                'If-Modified-Since': lastmod,
                'User-Agent': config.main.system_name + "/" + config.main.cg_sys_version + " (+" + config.main.cg_producthome + ")",
                'Accept': 'application/xml,application/atom+xml,application/rss+xml,application/javascript,application/json,text/plain,text/xml;q=0.9, */*;q=0.8',
                'Accept-Charset': 'utf-8;q=0.9, iso-8859-1;q=0.8',
                'Accept-Language': 'en-US, en;q=0.9, fr-CH, fr;q=0.8, en;q=0.7, de;q=0.6, *;q=0.5'
            };

            //console.log("LastMod: " + lastmod + "(" + f.lastmod + ")");

            request(opt, function (err, response, body) {
                var requesterror = err;
                var xml = '';
                var xmlstring = '';
                var newmod = 0;
                var neterr = false;
                var processbody = true;
                var contentHash = "";
                var alreadystored = false;
                var contentChanged = false;

                //Error handler
                if (err) {
                    neterr = true;
                    console.log("  " + f.title + " : (" + f.lastmodPretty + ") " + f.url + " : error on next line");
                    console.log(err);

                    if (typeof err.code !== "undefined") {
                        if (err.code == 'ETIMEDOUT') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET updated=0,lasthttpstatus=900 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        } else if (err.code == 'ECONNRESET') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=901 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        } else if (err.code == 'ENOTFOUND') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+10,lasthttpstatus=902 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        } else if (err.code == 'EAI_AGAIN') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=903 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        } else if (err.code == 'ECONNREFUSED') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+10,lasthttpstatus=905 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        } else if (err.code == 'EHOSTUNREACH') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=906 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        } else if (err.code == 'ESOCKETTIMEDOUT') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=907 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        } else if (err.code == 'HPE_INVALID_CONSTANT') {
                            dbcalls++;

                            if (f.url.charAt(f.url.length - 1) == '/') {
                                var newurl = f.url.substr(0, f.url.length - 1);
                                console.log("Error with url: [" + f.url + "]. Changing url to: " + newurl);
                                connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET url=?,lastcheck=UNIX_TIMESTAMP(now()),updated=0,lasthttpstatus=904 WHERE id=?', [newurl, f.id], function (err, result) {
                                    //if (err) throw err;
                                    if (err || result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                    dbcalls--;
                                });
                            } else {
                                connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),updated=0,lasthttpstatus=904 WHERE id=?', [f.id], function (err, result) {
                                    if (err) throw err;
                                    if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                    dbcalls--;
                                });
                            }
                        }

                        //If we didn't get a valid err.code then we just log this as an unknown error (999)
                    } else {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,lasthttpstatus=999 WHERE id=?', [f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }
                }

                //Assign the body
                xml = body;

                //Get content type
                var contype = "none";
                if (typeof response !== "undefined" && typeof response.headers !== "undefined" && 'content-type' in response.headers) {
                    contype = response.headers['content-type'];
                }

                //TODO: more work should be done here rather than offloading it all to feedscan.php
                //Body checks before further processing
                if (typeof body !== "undefined" &&
                    typeof body.toString === "function" &&
                    typeof response !== "undefined" &&
                    typeof response.statusCode !== "undefined" &&
                    typeof response.headers !== "undefined" &&
                    'content-type' in response.headers) {

                    xmlstring = body.toString();
                    contentHash = crypto.createHash('md5').update(xmlstring).digest("hex");

                    //console.log(response.headers);
                    //console.log(response.headers['content-type']);

                    var contentType = response.headers['content-type'];

                    //Encoding issues?
                    var charset = getParams(response.headers['content-type'] || '').charset;
                    xml = maybeTranslate(body, charset);

                    //If the content-type is not json, make sure it has the right xml parts
                    if (xmlstring.indexOf('<rss') < 0 &&
                        xmlstring.indexOf('<feed') < 0 &&
                        xmlstring.indexOf('<rdf') < 0 &&
                        contentType.indexOf('json') < 0 &&
                        response.statusCode === 200) {
                        //Debug
                        //writeFile(f.url.toLowerCase().substr(f.url.toLowerCase().indexOf('://') + 3).replace(/[^0-9a-zA-Z.\-_]/gi, '_') + redirectCodes.join('-'), body);

                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content=?,lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=?,contenttype=?,contenthash=? WHERE id=?', [xml, response.statusCode, contype, contentHash, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) {
                                console.log("Error updating database for feed: [" + f.url + "]");
                            } else {
                                alreadystored = true;
                            }
                            dbcalls--;
                        });
                        processbody = false;
                    }
                }

                //Get a hash of the feed raw content and determine if it changed or not
                if (typeof body !== "undefined" && typeof body.toString === "function") {
                    xmlstring = body.toString();
                    contentHash = crypto.createHash('md5').update(xmlstring).digest("hex");

                    //Did the content of the feed actually change?
                    if(f.contenthash == contentHash) {
                        //loggit(3, "Feed has not changed: [ "+f.contenthash+" | "+contentHash+" ]");
                        //contentChanged = false;
                    } else {
                        loggit(3, "TAIL -- Feed: ["+f.url+"] content has changed: [ "+f.contenthash+" | "+contentHash+" ]");
                        contentChanged = true;
                    }
                }

                //Now do standard response processing
                if (processbody && typeof response !== "undefined" && typeof response.statusCode !== "undefined" && !neterr) {
                    //Log some basic info
                    //console.log("  " + f.title + " : (" + f.lastmodPretty + ") " + f.url + " : " + response.statusCode);

                    if (typeof response.headers['Last-Modified'] !== "undefined") {
                        newmod = Math.floor(Date.parse(response.headers['Last-Modified']) / 1000);
                    } else {
                        newmod = Math.floor(Date.now() / 1000);
                    }
                    loggit(3, "Feed: [" + f.url + "] LastMod: [" + f.lastmod + " | " + newmod + "] Response: [" + response.statusCode + "]");

                    //2xx response
                    if (response.statusCode / 100 === 2) {
                        if (xml.length > 3040000) {
                            console.log("  Error:  Feed content is too large.");
                        } else {
                            dbcalls++;

                            var contentUpdated = 0;
                            if(contentChanged) {
                                contentUpdated = 1;
                            }

                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content=?,lastcheck=UNIX_TIMESTAMP(now()),lastmod=?,updated=?,lasthttpstatus=?,lastgoodhttpstatus=UNIX_TIMESTAMP(now()),contenttype=?,contenthash=? WHERE id=?', [xml, newmod, contentUpdated, response.statusCode, contype, contentHash, f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) {
                                    console.log("  Error updating database for feed: [" + f.url + "]");
                                } else {
                                    //loggit(3, "Feed: ["+f.url+"] content has changed.");
                                }
                                dbcalls--;
                            });
                        }
                    }

                    //3xx response
                    else if (response.statusCode === 302) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),lasthttpstatus=302,lastgoodhttpstatus=UNIX_TIMESTAMP(now()),contenttype=? WHERE id=?', [contype, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }
                    else if (response.statusCode === 304) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),lasthttpstatus=304,lastgoodhttpstatus=UNIX_TIMESTAMP(now()),contenttype=? WHERE id=?', [contype, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }
                    else if (response.statusCode === 307) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),lasthttpstatus=307,lastgoodhttpstatus=UNIX_TIMESTAMP(now()),contenttype=? WHERE id=?', [contype, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }
                    else if (response.statusCode === 308) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),lasthttpstatus=308,lastgoodhttpstatus=UNIX_TIMESTAMP(now()),contenttype=? WHERE id=?', [contype, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }

                    //4xx response
                    else if (response.statusCode / 100 === 4) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+4,lasthttpstatus=?,contenttype=?,contenthash="" WHERE id=?', [response.statusCode, contype, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }

                    //5xx response
                    else if (response.statusCode / 100 === 5) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+5,lasthttpstatus=?,contenttype=?,contenthash="" WHERE id=?', [response.statusCode, contype, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }

                    //Response we don't handle
                    else {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),errors=errors+1,lasthttpstatus=?,contenttype=?,contenthash="" WHERE id=?', [response.statusCode, contype, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                            dbcalls--;
                        });
                    }

                    // console.log("HREF: "+response.request.uri.href+"["+f.url+"]");
                    // console.log(typeof response);
                    // console.log(typeof response.request);
                    // console.log(typeof response.request.uri);
                    // console.log(typeof response.request.uri.href);
                    // console.log(typeof redirectCodes[0]);
                    // console.log(redirectCodes[0]);

                    //Handle redirections, where the final url is different than the original one requested
                    if (typeof response !== "undefined" &&
                        typeof response.request !== "undefined" &&
                        typeof response.request.uri !== "undefined" &&
                        typeof response.request.uri.href === "string" &&
                        response.request.uri.href.indexOf("http") === 0 &&
                        response.request.uri.href !== f.url &&
                        0 in redirectCodes) {

                        console.log("  Redirected from: [" + f.url + "] to: [" + response.request.uri.href + " | " + redirectCodes[0] + " | " + response.statusCode + "]");
                        loggit(3, "Aggrivate: Feed url redirect from: [" + f.url + "] to: [" + response.request.uri.href + " | " + redirectCodes[0] + " -> " + response.statusCode + "].");

                        if (redirectCodes[0] === 301) {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET url=?,lasthttpstatus=301,lastgoodhttpstatus=UNIX_TIMESTAMP(now()),contenttype=? WHERE id=?', [response.request.uri.href, contype, f.id], function (err, result) {
                                if (err) {
                                    console.log("Error updating feed url location in database. Err: [" + err.code + " | "+f.url+" -> "+response.request.uri.href+"] ");
                                    loggit(2, "Error updating feed url location in database. Err: [" + err.code + " | "+f.url+" -> "+response.request.uri.href+"] ");
                                }
                                if (err && err.code == 'ER_DUP_ENTRY') {
                                    console.log("  Result: " + result);
                                    loggit(3, "  Result: " + result);
                                }
                                //if (result.affectedRows === 0) console.log("Error updating database for feed: ["+f.url+"]");
                                dbcalls--;
                            });
                        } else if (redirectCodes[0] === 308) {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET url=?,lasthttpstatus=308,lastgoodhttpstatus=UNIX_TIMESTAMP(now()),contenttype=? WHERE id=?', [response.request.uri.href, contype, f.id], function (err, result) {
                                if (err) {
                                    console.log("Error updating feed url location in database. Err: [" + err.code + " | "+f.url+" -> "+response.request.uri.href+"] ");
                                    loggit(2, "Error updating feed url location in database. Err: [" + err.code + " | "+f.url+" -> "+response.request.uri.href+"] ");
                                }
                                //if (result.affectedRows === 0) console.log("Error updating database for feed: ["+f.url+"]");
                                dbcalls--;
                            });
                        }
                    }

                } else {
                    var statCode = 0;
                    if (typeof response !== "undefined") {
                        statCode = response.statusCode || -1;
                    }

                    //There was a structural error in the feed content
                    if(!requesterror && !alreadystored) {
                        if (!processbody) {
                            console.log("There was a structural error in the feed content for: [" + f.url + "] " + statCode);
                            loggit(2, "There was a structural error in the feed content for: [" + f.url + "] " + statCode);
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content=?,lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=?,contenttype=?,contenthash=? WHERE id=?', [xmlstring, statCode, contype, contentHash, f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });

                            //If neterr is set then we already handled this in the error handler section so skip
                        } else {
                            console.log("Something went wrong with feed: [" + f.url + "] but we don't handle that error yet " + statCode);
                            loggit(2, "Something went wrong with feed: [" + f.url + "] but we don't handle that error yet " + statCode);
                            loggit(2, requesterror);
                            loggit(2, response);
                            loggit(2, body);
                            if (statCode == -1) {
                                console.log(response);
                            }
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,lasthttpstatus=?,contenttype="",contenthash="" WHERE id=?', [statCode, f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating database for feed: [" + f.url + "]");
                                dbcalls--;
                            });
                        }
                    }
                }

                netcalls--;
            });
        })(feed);

        //DEBUG: Break here when testing
        //break;
    }
});
dbcalls--;


function loggit(lognum, message) {
    //Timestamp for this log
    tstamp = new Date(Date.now()).toLocaleString();
    var fd;

    //Open the file
    switch (lognum) {
        case 1:
            if (config.logging.log_errors_only == 1) {
                return true;
            }
            fd = fs.createWriteStream('/opt/cartulary/' + config.folders.log + '/' + config.logging.acclog, {'flags': 'a'});
            break;
        case 2:
            fd = fs.createWriteStream('/opt/cartulary/' + config.folders.log + '/' + config.logging.errlog, {'flags': 'a'});
            break;
        case 3:
            fd = fs.createWriteStream('/opt/cartulary/' + config.folders.log + '/' + config.logging.dbglog, {'flags': 'a'});
            break;
    }

    //Write the message
    fd.end("[" + tstamp + "] [LOCAL] (" + __filename + ") " + message + "\n");

    //Return
    return true;
}

function writeFile(filename, content) {
    fd = fs.createWriteStream('/tmp' + filename, {'flags': 'a'});
    fd.end(content);

    return true;
}

function getParams(str) {
    var params = str.split(';').reduce(function (params, param) {
        var parts = param.split('=').map(function (part) {
            return part.trim();
        });
        if (parts.length === 2) {
            params[parts[0]] = parts[1];
        }
        return params;
    }, {});
    return params;
}

function maybeTranslate(content, charset) {
    var iconv;
    // Use iconv if its not utf8 already.
    if (!iconv && charset && !/utf-*8/i.test(charset)) {
        try {
            iconv = new Iconv(charset, 'utf-8');
            //console.log('Converting from charset %s to utf-8', charset);
            iconv.on('error', function () {
                console.log("Error translating with Iconv.");
            });
            // If we're using iconv, stream will be the output of iconv
            // otherwise it will remain the output of request
            return iconv.convert(new Buffer(content, 'binary')).toString('utf8')
            //res = res.pipe(iconv);
        } catch (err) {
            //res.emit('error', err);
            console.log("Error translating with Iconv. Err: " + err);
        }
    }
    return content;
}

dbcheck = setInterval(function () {
    console.log("--- Still: [" + dbcalls + "] database calls and: [" + netcalls + "] network requests. Feed count: [" + feedcount + "]. Netwait: [" + netwait + "].")
    if (dbcalls === 0 && (netcalls === 0 || netwait === 0)) {
        connection.end();
        console.log("Aggrivate finished running.");
        loggit(3, "DEBUG: Aggrivate finished running.");
        process.exit(0);
    }
    if (dbcalls === 0) {
        netwait--;
    }
    if (dbcalls > 0) {
        netwait = 30;
    }
}, 5000);
