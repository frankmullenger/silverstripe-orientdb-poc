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

## Usage

* Start OrientDB
	cd ~/path/to/orientdb-graphed-1.5.0/bin
	./server.sh
* Create OrientDB database
	cd ~/path/to/orientdb-graphed-1.5.0/bin
	./console.sh
	create database remote:localhost/dbname root <rootpassword> plocal document
* Update $databaseConfigOrient in _config.php
* Browse to yourdomain.com/orient
* Build and populate database with test objects using links provided
* Create/Read/Update/Delete test objects using interface

