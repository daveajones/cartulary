#### What is Cartulary?
  It's part of the new Freedom Controller project:  http://freedomcontroller.com

  The larger goal of the project is an attempt at building a distributed social network
  through the use of standard RSS and OPML feeds.  This is the first product.

  We call Cartulary a digital archiver.  But, you could also call it a social network 
  in a box.  It's an RSS reader, RSS aggregator, readability tool, article archiver, 
  microblogger, social graph manager and reading list manager.

  You can publish all of your feeds into a single social outline(OPML) with it, so that
  other people can easily subscribe to all of your stuff at once.  You can save full
  text articles into a feed as you read things on the web, then later export them into
  an OPML file using date ranges and searches.

  It all runs on a standard LAMP stack, and it supports multiple users.  So, if you're
  a techy person, you can run your own server for all your friends.

  We have a google group for announcements, questions and install help here:

  https://groups.google.com/forum/#!forum/sopml


#### LICENSE:
  All original code in this package is currently licensend under the CDDL license.
               http://opensource.org/licenses/CDDL-1.0
  
  All non-original functions/libraries remain under their respective licenses or
  are listed with source attribution if the code is public domain or unlicensed.

  Concord Outliner: GPL  (https://github.com/scripting/concord)
  is the browser engine of Fargo: http://fargo.io


#### INSTALL:
  The software will run on any unix-like system with a LAMP stack fairly easily, but it 
  installs and upgrades easiest on Unbuntu LTS 12, 14 or 16 etc.  That's what is assumed in the install
  documentation.  Running on any other system will require installing by hand, which is
  not hard.

  Although the system will run without Amazon S3 configured, it loses a lot of the best
  functionality.  Amazon S3 is really cheap.  You should do it. :-)

  Example: Ubuntu 12,14,16 LTS [on Amazon Ec2]:

    1. [Launch an "small" Ubuntu 12 LTS ec2 instance.]
    2. [Create an Elastic IP and assign it to your instance.]
    3. Create a DNS A or CNAME record for the server and point it to your elastic ip.
    4. Create two S3 buckets: 1 for holding user data and 1 for holding server backups.
    5. [Optional] Create a DNS cname to point to your S3 user bucket. This makes life
       easier for your users.
    6. Download the github zip archive.
    7. Unzip the archive.
	8. You can open "cartulary-master/INSTALL" and edit the "INSTALLDIR" variable to
       install in a different directory than the default at this point.
    9. Without going into the extracted directory, run "sudo cartulary-master/INSTALL".

    ** The INSTALLation script is meant to ONLY be run on a fresh version of Ubuntu 12. I
    take no responsibility for how bad you wreck your system if you run it on anything 
    other than that.

  Other (Linux, Mac):

    1. Create a DNS A or CNAME record for your server or configure dynDNS.
    2. Create two S3 buckets: 1 for holding user data and 1 for holding server backups.
    3. [Optional] Create a DNS cname to point to your S3 user bucket. This makes life
       easier for your users.
    4. Download the github zip archive.
    5. Unzip the archive.
    6. Open the INSTALL file and follow along, making changes where necessary
       for your particular environment.
   
  
