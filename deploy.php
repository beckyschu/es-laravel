<?php
namespace Deployer;
require 'recipe/laravel.php';

// Configuration

set('repository', 'git@github.com:opmonk/dan-laravel.git');

//set('ssh_type', 'native');
//set('ssh_multiplexing', true);

add('shared_files', []);
add('shared_dirs', []);

add('writable_dirs', []);

// Servers

server('production', '206.191.153.213')
    ->user('ipshark')
    ->identityFile()
    ->set('deploy_path', '/home/ipshark/deployments');

// Register extra deployment tasks

desc('Install NPM dependencies');
task('deploy:npm', function () {
    run('cd {{release_path}} && NODE_ENV=production npm install');
});

// Override the main task

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:npm',
    'deploy:writable',
    'artisan:view:clear',
    'artisan:cache:clear',
//    'artisan:config:cache', // Don't do this as it screws up the .env
    'artisan:optimize',
    'deploy:symlink',
    'artisan:queue:restart',
    'deploy:unlock',
    'cleanup',
]);

// Register extra tasks

desc('Backup database to Dropbox');
task('backup', function () {
    $output = run('if [ -f {{deploy_path}}/current/artisan ]; then {{bin/php}} {{deploy_path}}/current/artisan shark:backup; fi');
    writeln('<info>' . $output . '</info>');
});