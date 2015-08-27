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

//Pull the feed list
dbcalls++;
connection.query('SELECT id,title,url,lastmod,createdon FROM ' + config.tables.table_newsfeed + ' WHERE errors < 10 AND (lastupdate > '+monthago+' OR lastcheck = 0) ORDER by lastcheck ASC', function(err,rows,fields) {
    //Bail on error
    if(err) throw err;

    for (var row in rows) {
        var feed = rows[row];

        //Give the console what we're checking
        //console.log(rows[row].id + ' : ', rows[row].url);

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
            opt.headers = {'If-Modified-Since': lastmod};

            //loggit(3, "LastMod: " + lastmod + "(" + f.lastmod + ")");
            //console.log("LastMod: " + lastmod + "(" + f.lastmod + ")");

            request(opt, function(err, response, body) {
                var xml = '';
                var newmod = 0;

                //Loggit
                if(err) {
                    console.log("  " + f.title + " : (" + f.lastmodPretty + ") " + f.url + " : " + err);
                } else {
                    console.log("  " + f.title + " : (" + f.lastmodPretty + ") " + f.url + " : " + response.statusCode);
                }

                //Responses
                if(!err && (response.statusCode / 100) === 2) {
                    //console.log(response.statusCode);
                    xml = body;
                    if( typeof response.headers['Last-Modified'] !== "undefined" ) {
                        newmod = Date.parse(response.headers['Last-Modified']) / 1000;
                    } else {
                        newmod = Date.now() / 1000;
                    }

                    dbcalls++;

                    connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET content=?,lastcheck=UNIX_TIMESTAMP(now()),lastmod=?,updated=1,errors=0 WHERE id=?', [xml, newmod, f.id], function (err, result) {
                        if (err) throw err;
                        if (result.affectedRows === 0) {
                            console.log("  Error updating feed content in database.");
                        } else {
                            console.log("  Feed updated.");
                        }
                        dbcalls--;
                    });

                }
                else
                if(!err && response.statusCode === 304) {
                    //console.log("  " + f.title + " : " + f.url + " : " + response.statusCode);
                }
                else
                if(err || (typeof response.statusCode !== "undefined" && response.statusCode >= 400) )  {
                    dbcalls++;
                    connection.query('UPDATE ' + config.tables.table_newsfeed + ' SET lastcheck=UNIX_TIMESTAMP(now()),errors=errors+1 WHERE id=?', [f.id], function (err, result) {
                        if (err) throw err;
                        if (result.affectedRows === 0) console.log("Error updating feed content in database.");
                        dbcalls--;
                    });
                }

                netcalls--;
            });
        })(feed);
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