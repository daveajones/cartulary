Assuming you're going to run your server on Amazon Ec2, the typical procedure for setup 
would go like this:

1. Launch an "small" Ubuntu 12 LTS ec2 instance.
2. Create an Elastic IP and assign it to your instance.
3. Create a DNS A or CNAME record for the server and point it to your elastic ip.
4. Create two S3 buckets: 1 for holding user data and 1 for holding server backups.
5. [Optional] Create a DNS cname to point to your S3 user bucket. This makes life
   easier for your users.
6. Download the github zip archive.
7. Unzip the archive.
8. Without going into the extracted directory, run "sudo cartulary-master/INSTALL".



** The INSTALLation script is meant to ONLY be run on a fresh version of Ubuntu 12. I
take no responsibility for how bad you wreck your system if you run it on anything 
other than that.
