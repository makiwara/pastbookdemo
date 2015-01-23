# Pastbookdemo

This is LAMP-based installation.

## home
Home is built static to the moment.
All you need is to go http://pastbook.projectabove.com/home/index.html

## new
This is the brand new version of Landing Page.
Also static, available at http://pastbook.projectabove.com/new/index.html

## app
App is built on Silex.
It requires Composer to set things up.
It also requires cURL PHP extension to be installed.

You will need to create config/db.php from db_sample.php: change USERNAME/PASSWORD to your mySQL credentials or use alternate configuration of Doctrine DBAL.

You will need to put 'curl <YOURWEBSITE>/upload' to crontab file to provide photo uploader with independent schedule.

## Scalability and technical debt for ‘app’
This is a rather quick prototype.

It is a lot to be done in order to clean stuff up. Firstly, we will need to refactor src/app.php in order to connect DBAL, OAuth and Uploader as regular Silex service providers. As we will scale our app, I would recommend to compartmentalize controllers as well. For now src/controllers.php is a mess.

Photos are stored locally. First improvement would be to use separate cloud storage. We will need to rewrite Uploader class for this. We could also tune up a number of photos to be uploaded per one /upload. After that we could scale our system in the usual way: providing a number of instances running PHP code. Race conditions on the queue are possible but rare, and they will bring no harm in terms of performance and consistency.

DBAL is written quite simply with no error handling. For now it just incapsulates all SQL from business logic and allows us to refactor these parts separately. We will need to provide error handling and unit-tests.

OAuth is also pretty crude. Instagram apiCallback should be handled in a more concise way. I have written a small abstraction which will allow us to connect a number of cloud storages—it could be seen in src/oauth.php. That is a first step to make things right in this part. It would be nice to have unit-tests for OAuth.

Email template is very primitive. We could go for better email handling. It would be useful to separate email handling from other parts of system as it was already done for uploader. 

We could also open a nice pop-up for authorization.



