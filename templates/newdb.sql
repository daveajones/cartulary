create database cartulary;
create user cartulary identified by 'cartulary';
grant usage on *.* to cartulary@localhost identified by 'cartulary';
grant all privileges on cartulary.* to cartulary@localhost;

