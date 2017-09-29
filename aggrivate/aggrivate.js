//Requires
var mysql = require('mysql');
var request = require('request');
var fs = require('graceful-fs');
var ini = require('ini');
var iconv = require('iconv-lite');

//Globals
var netcalls = 0;
var dbcalls = 0;
var dbcheck = 0;
var query = 0;
var checkall = false;
var checkone = false;
var ckoneurl = '';

//Get command line args
process.argv.forEach((val, index, array) => {
    console.log(index + ": [" + val + "]");
    if( index >= 2 && val === "checkall") {
        console.log("Checking all feeds.");
        checkall = true;
    }

    if( !checkall && index >=2 && val.indexOf('http') !== -1 ) {
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
    host    :   config.database.dbhost,
    user    :   config.database.dbuser,
    password:   config.database.dbpass,
    database:   config.database.dbname
});
connection.connect();

//Timestamp for one month ago
var monthago = (Date.now() / 1000) - (28 * 86400);

//Assemble query
var query = 'SELECT id,title,url,lastmod,createdon FROM ' + config.tables.table_newsfeed + ' WHERE (errors < 100 OR (lastupdate > '+monthago+' OR lastcheck = 0 OR lastmod = 0)) AND dead=0 ORDER by lastcheck ASC';
if(checkall) {
    query = 'SELECT id,title,url,lastmod,createdon FROM ' + config.tables.table_newsfeed + ' WHERE dead=0 ORDER by lastcheck ASC';
}
if(checkone) {
    query = 'SELECT id,title,url,lastmod,createdon FROM ' + config.tables.table_newsfeed + ' WHERE url="' + ckoneurl + '" ORDER by lastcheck ASC';
}

//Pull the feed list
dbcalls++;
connection.query(query, function(err,rows,fields) {
    //Bail on error
    if(err) throw err;

    for (var row in rows) {
        var feed = rows[row];

        //Ignore feeds that dont start with http scheme

        //Give the console what we're checking
        //console.log(rows[row].id + ' : ', rows[row].url);

        //Don't attempt to fetch feeds with non-fqdn urls
        if( feed.url.toLowerCase().indexOf('http') !== 0 ) {
            console.log("Skipping non-fqdn feed url: ["+feed.url+"]");
            continue;
        }

        //Make the get request
        (function(f) {
            netcalls++;
            var lastmod = 0;
            var opt = {
                uri: f.url,
                followRedirect: true,
                strictSSL: false,
                encoding: null
            };

            if(f.lastmod === 0) {
                lastmod = new Date((Date.now() - (86400 * 1000))).toUTCString();
            } else {
                lastmod = new Date(f.lastmod * 1000).toUTCString();
            }
            f.lastmodPretty = lastmod;
            opt.headers = {
                'If-Modified-Since': lastmod,
                'User-Agent': 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0'
            };

            //loggit(3, "LastMod: " + lastmod + "(" + f.lastmod + ")");
            console.log("LastMod: " + lastmod + "(" + f.lastmod + ")");

            request(opt, function(err, response, body) {
                var xml = '';
                var newmod = 0;
                var neterr = false;

                //Error handler
                if(err) {
                    neterr = true;
                    console.log("  " + f.title + " : (" + f.lastmodPretty + ") " + f.url + " : error on next line");
                    console.log(err);

                    if( typeof err.code !== "undefined" ) {
                        if(err.code == 'ETIMEDOUT') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=900 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                                dbcalls--;
                            });
                        } else
                        if(err.code == 'ECONNRESET') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=901 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                                dbcalls--;
                            });
                        } else
                        if(err.code == 'ENOTFOUND') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+10,lasthttpstatus=902 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                                dbcalls--;
                            });
                        } else
                        if(err.code == 'EAI_AGAIN') {
                            dbcalls++;
                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=903 WHERE id=?', [f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                                dbcalls--;
                            });
                        } else
                        if(err.code == 'HPE_INVALID_CONSTANT') {
                            dbcalls++;

                            if(f.url.charAt(f.url.length - 1) == '/') {
                                var newurl = f.url.substr(0, f.url.length - 1);
                                console.log("Changing url to: " + newurl);
                                connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET url=?,lastcheck=UNIX_TIMESTAMP(now()),updated=0,lasthttpstatus=904 WHERE id=?', [newurl,f.id], function (err, result) {
                                    //if (err) throw err;
                                    if (err || result.affectedRows === 0) console.log("Error updating feed content in database.");
                                    dbcalls--;
                                });
                            } else {
                                connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),updated=0,lasthttpstatus=904 WHERE id=?', [f.id], function (err, result) {
                                    if (err) throw err;
                                    if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                                    dbcalls--;
                                });
                            }
                        }

                    //If we didn't get a valid err.code then we just log this as an unknown error (999)
                    } else {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,lasthttpstatus=999 WHERE id=?', [f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
                    }
                }

                //Body content cleanup
                //Trim blank space from start and end
                // if( typeof body !== "undefined" && typeof body.toString === "function") {
                //     xml = body.toString();
                //     xml = xml.trim();
                //
                //     //Remove non-xml from after RSS/ATOM closing tags
                //     if( xml.indexOf('<?xml') !== -1 && xml.indexOf('<rss') !== -1 && xml.indexOf('</rss>') !== -1 ) {
                //         xml = xml.substr(0, xml.indexOf('</rss>') + ('</rss>').length);
                //     }
                //     if( xml.indexOf('<?xml') !== -1 && xml.indexOf('<feed') !== -1 && xml.indexOf('</feed>') !== -1 ) {
                //         xml = xml.substr(0, xml.indexOf('</feed>') + ('</feed>').length);
                //     }
                // }
                // // console.log(typeof body);
                // // console.log(typeof body.toString)
                xml = body;

                if( typeof response !== "undefined" && typeof response.statusCode !== "undefined" ) {
                    //Log some basic info
                    console.log("  " + f.title + " : (" + f.lastmodPretty + ") " + f.url + " : " + response.statusCode);

                    //2xx response
                    if(response.statusCode / 100 === 2) {
                        if( typeof response.headers['Last-Modified'] !== "undefined" ) {
                            newmod = Date.parse(response.headers['Last-Modified']) / 1000;
                        } else {
                            newmod = Date.now() / 1000;
                        }

                        if(xml.length > 1040000) {
                            console.log("  Error:  Feed content is too large.");
                        } else {
                            dbcalls++;

                            connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content=?,lastcheck=UNIX_TIMESTAMP(now()),lastmod=?,updated=1,errors=0,lasthttpstatus=?,lastgoodhttpstatus=UNIX_TIMESTAMP(now()) WHERE id=?', [xml, newmod, response.statusCode, f.id], function (err, result) {
                                if (err) throw err;
                                if (result.affectedRows === 0) {
                                    console.log("  Error updating feed content in database.");
                                } else {
                                    console.log("  Feed updated.");
                                }
                                dbcalls--;
                            });
                        }
                    }

                    //3xx response
                    else if( response.statusCode === 304 ) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),lasthttpstatus=304,lastgoodhttpstatus=UNIX_TIMESTAMP(now()) WHERE id=?', [f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
                    }
                    else if( response.statusCode === 307 ) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),lasthttpstatus=307,lastgoodhttpstatus=UNIX_TIMESTAMP(now()) WHERE id=?', [f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
                    }
                    else if( response.statusCode === 308 ) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),lasthttpstatus=308,lastgoodhttpstatus=UNIX_TIMESTAMP(now()) WHERE id=?', [f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
                    }

                    //4xx response
                    else if( response.statusCode / 100 === 4 ) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+4,lasthttpstatus=? WHERE id=?', [response.statusCode, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
                    }

                    //5xx response
                    else if( response.statusCode / 100 === 5 ) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+5,lasthttpstatus=? WHERE id=?', [response.statusCode, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
                    }

                    //Response we don't handle
                    else {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),errors=errors+1,lasthttpstatus=? WHERE id=?', [response.statusCode, f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
                    }

                } else {
                    //If neterr is set then we already handled this in the error handler section so skip
                    if(!neterr) {
                        dbcalls++;
                        connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content="",lastcheck=UNIX_TIMESTAMP(now()),updated=0,errors=errors+1,lasthttpstatus=0 WHERE id=?', [f.id], function (err, result) {
                            if (err) throw err;
                            if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                            dbcalls--;
                        });
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

dbcheck = setInterval(function() {
    console.log("--- Still: [" + dbcalls + "] database calls and: ["+ netcalls +"] network requests outstanding.")
    if( dbcalls === 0 && netcalls === 0) {
        connection.end();
        process.exit(0);
    }
}, 5000);

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
            fd = fs.createWriteStream('/opt/cartulary/'+config.folders.log+'/' + config.logging.acclog, {'flags': 'a'});
            break;
        case 2:
            fd = fs.createWriteStream('/opt/cartulary/'+config.folders.log+'/' + config.logging.errlog, {'flags': 'a'});
            break;
        case 3:
            fd = fs.createWriteStream('/opt/cartulary/'+config.folders.log+'/' + config.logging.dbglog, {'flags': 'a'});
            break;
    }

    //Write the message
    fd.end("["+tstamp+"] [LOCAL] (" + __filename + ") " + message + "\n");

    //Return
    return true;
}