# Silverstripe OrientDB Connector PoC

## Description

This proof of concept is to investigate the feasibility of using OrientDB graph database as a database for Silverstripe.

## Requirements

* PHP >= 5.3.2
* Git
* Composer
* OrientDB >= 1.5.0

## Installation

* git clone https://github.com/frankmullenger/silverstripe-orientdb-poc.git ./your-project
* composer install
* /dev/build?flush=1

### OrientDB
[Guide to installing and getting started with OrientDB](http://www.sitepoint.com/a-look-at-orientdb-the-graph-document-nosql/)

## Usage

#### Start OrientDB
```bash
cd ~/path/to/orientdb-graphed-1.5.0/bin
./server.sh
```

#### Create OrientDB database
```bash
cd ~/path/to/orientdb-graphed-1.5.0/bin
./console.sh
create database remote:localhost/dbname root <rootpassword> plocal document
```

#### Configure
Update ```$databaseConfig``` in _config.php, see [config docs for an example](https://github.com/frankmullenger/silverstripe-orientdb-poc/blob/master/app/docs/en/Config.md).

```php
global $databaseConfig;
$databaseConfig = array(
	'type' => 'OrientDatabase',
	'server' => 'localhost',
	'port' => 2424,
	'serverusername' => 'root',
	'serverpassword' => 'your root password',
	'username' => 'admin',
	'password' => 'admin',
	'database' => 'SS_orientdb-app',
	'cluster' => '',
	'path' => '/Users/fmullenger/Scripts/orientdb-graphed-1.5.0/databases',
);

//This is pretty yuck, possibly need dependency injection or namespace solution
Object::useCustomClass('DataList', 'OrientDataList');
Object::useCustomClass('RelationList', 'OrientRelationList');
Object::useCustomClass('HasManyList', 'OrientHasManyList');
Object::useCustomClass('ManyManyList', 'OrientManyManyList');
Object::useCustomClass('ForeignKey', 'OrientForeignKey');

//Bypass authentication for access to the admin area
Object::useCustomClass('MemberLoginForm', 'VoidLoginForm');
Authenticator::register_authenticator('VoidAuthenticator');
Authenticator::set_default_authenticator('VoidAuthenticator');
Authenticator::unregister_authenticator('MemberAuthenticator');

//Hide security admin for now
CMSMenu::remove_menu_item('SecurityAdmin');
```

Set up a default admin user/password because we essentially bypass authentication for now, in \_ss\_environment.php:
```php
define('SS_DEFAULT_ADMIN_USERNAME', 'admin');
define('SS_DEFAULT_ADMIN_PASSWORD', 'password');
```

#### Frontend
* Browse to yourdomain.com/orient
* Build and populate database with test objects using links provided
* Create/Read/Update/Delete test objects using interface.

#### Backend
* Browser to yourdomain.com/admin
* Log in using the admin username and password you set up earlier
* Edit objects from the available model admins

__Some caveats for the backend:__  
Filtering and pagination does not currently work. Creating related dataobjects when viewing/editing another dataobject does not currently work.



