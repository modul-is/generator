# Generator
Simple command line interface for generating php classes from mysql table

## Installation
Install via composer.json, do not forget to add composer handle to project's composer.json

	"scripts":
    {
        "post-install-cmd": [
            "ModulIS\\Generator\\ComposerHandler::copy"
        ],
        "post-update-cmd": [
            "ModulIS\\Generator\\ComposerHandler::copy"
        ]
    }

## Setup
Nothing else to setup, except `local.neon` - used as source for database

## How to use
Once you install extension, open `vendor\bin` directory in cmd(powershell requires `.\` before command -> `.\generate`)
There is no need to register factories or repository, all is done automatically

    generate <mysql_table> [--module=<module_name>] [--type=<type>] [--db=<database>]

### generate
`generate` => command to start generating

### mysql_table
`mysql_table` => table name which will be used for php classes generating
- if table has prefix, classes will also have prefix e.g. ins_insurance will generate InsInsuranceEntity etc

### module_name
`module_name` => name under which module new classes will be generated to, default is `AdminModule`
- module_name has to be in `PascalCaseLikeThis`
- module_name doest not have to contain `Module` - automatically added if missing

### type
`type` => decides which files will be generated, default is `all`
- options
    - all (the whole module)
    - entity
    - repository
    - form
    - grid
    - presenter
    - template

### database
`database` => decides which database from `local.neon` will be used
- if your project only uses one database or uses multiple but you want to use `default`, it will be selected automatically and this parameter is not needed

## What is generated

### Entity
Namespace, class, properties, functions to return new datetime object instead of string for date and datetime columns

### Repository
Namespace, class, table and entity property, overridden getBy and getByID

### Form
Namespace, class, repository, properties, attached(with checks), form, formSuccess

### Grid
Namespace, class, grid, handles, editForm

### Presenter
Factory inject, createComponent function, actionFormEdit

### Template
Latte template with control component

### Dials
Namespace, class, constants, functions - constants and translation needs to be changed
-	Only generated from enum and char columns  
