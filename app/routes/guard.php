<?php

/*
 |----------------------------------------------------------------------------------
 | The URI Access file
 |----------------------------------------------------------------------------------
 | This handles file access rule for each route
 |
 | @expression {int}    - Integer values
 | @expression {slug}   - Alphanumerical values
 | @expression {any}    - Every character except slashes (/)
 | @expression {wild}   - Every character including slashes
 |
 | Roles are defined as follows:
 | - session: true          - Requires user to be logged in
 | - session: false         - Does not require user to be logged in
 | - access: all            - Accessible to all users (logged in or not)
 | - access: guest          - Accessible to guests only (not logged in)
 | - access: user           - Accessible to user with role user
 | - access: [ ...roles ]   - Accessible to users with the specified roles
 |
 */
return [

    'auth/login' => [ 'session' => false, 'access' => 'guest'],
    'auth/register' => [ 'session' => false, 'access' => 'guest'],
    
    'app' => [ 'session' => true, 'access' => 'all' ],
    'app/{wild}' => [ 'session' => true, 'access' => 'all' ]

];
