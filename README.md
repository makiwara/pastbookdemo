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

## Development plan

* DONE design and static HTML/CSS
* DONE clientside Javascript simulation of upload
* IN PROGRESS database scheme
* import metadata from Instagram
* import images from Instagram
* send email on finish