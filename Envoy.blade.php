 @include('GetServerService.php');
@setup
    require __DIR__.'/vendor/autoload.php';
    (new \Dotenv\Dotenv(__DIR__, '.env'))->load();
    $now = new DateTime();
    $deployService = new GetServerService();
    $productionServers = $deployService->getServersArray();
    $names = array_keys($productionServers);
    $serverDetails = $deployService->getServerSettings($server);
    $webhook = getenv('WEB_HOOK');

    $local_env = getenv('APP_ENV');

    if ( !isset($server) ) throw new Exception("Server name is not set.\nServer names to use are:\n - " . implode("\n - ", $names) . "\n\n eg. envoy run deploy --server=server.com");
    
    $dateDisplay = $now->format('Y-m-d H:i:s');

    if ( is_null($serverDetails) ) throw new Exception("Server not found with the name $server. \nServer names to use are:\n - " . implode("\n - ", $names) . "\n\n eg. envoy run deploy --server=server.com");

    $gitbranch = isset($branch) ? $branch : "master";
    $laraveldirectory = $serverDetails['location'];
    $origin = "origin";

@endsetup

@servers($productionServers);
@story('deploy', ['on' => $server])
     start
     git
     composer
     migrations-status
     migrations
     artisan-clear
     notify
@endstory

@story('deploy-build', ['on' => $server])
     start
     git
     npm-build
     notify
@endstory

@story('test-connection', ['on' => $server])
    test-start
@endstory

@task('test-start')
    echo " --- Testing location deployment for {{ $server }} on branch {{ $gitbranch }} and origin is {{$origin}} @ {{ $dateDisplay }}";
    cd {{ $serverDetails['location'] }}
    pwd
    echo " --- Connected successfully"
    git branch -vvv
@endtask

@task('start')
    echo " --- Starting deployment for {{ $server }} on branch {{ $gitbranch }}: {{ $dateDisplay }}";
    cd {{ $serverDetails['location'] }}
    pwd
@endtask

@task('list-servers', ['on'=>'localhost'])

@endtask

@task('git', ['on'=> $server])

    echo " --- Pulling from git on branch {{$gitbranch}}";
    cd {{ $serverDetails['location'] }}
    git fetch {{$origin}} --prune
    git checkout {{ $gitbranch }} -f
    git reset --hard {{$origin}}/{{$gitbranch}}
    git status
    echo " --- Git updated for {{$gitbranch}} on {{ $server }}";
@endtask

@task('composer', ['on'=> $server])
    echo " --- Installing Composer dependencies....";
    echo " --- Changing to laravel directory";
    cd {{ $laraveldirectory }}
    
    @if ($local_env == 'production')
        echo " --- Installing composer dependencies -- no dev ";
        composer install -v --no-dev
    @else 
        echo " --- Installing composer dependencies ";
        composer install -v
    @endif
    
    composer dump-autoload
    echo " --- Finished the composer task for {{ $server }}";
@endtask

@task('npm-install', ['on'=> $server] )
    echo " --- Running npm install:";
    echo " --- Changing to laravel directory";
    cd {{ $laraveldirectory }}
    npm install
@endtask

@task('npm-prod', ['on'=> $server] )
    echo " --- Running npm run prod:";
    echo " --- Changing to laravel directory";
    cd {{ $laraveldirectory }}
    npm run production
@endtask

@task('npm-build', ['on'=> $server] )
    echo " --- Running npm run build:";
    cd {{ $laraveldirectory }}
    npm install && npm run build
@endtask


@task('migrations-status', ['on'=> $server] )
    echo " --- Migration Status:";
    echo " --- Changing to laravel directory";
    cd {{ $laraveldirectory }}
    php artisan migrate:status
    echo " --- Do you need to run migration on  {{ $server }}?";
@endtask

@task('artisan-clear', ['on'=> $server] )
    echo " --- Clearing";
    cd {{ $laraveldirectory }}
    php artisan nova:publish
    php artisan view:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan config:clear
    
@endtask

@task('migrations', ['on'=> $server])
    echo " --- Running migrations";
    echo " --- Moving to laravel directory";
    cd {{ $laraveldirectory }}
    php artisan migrate --env=deploy
    echo " --- Finished the migrations task for {{ $server }}";
@endtask

@task('yarn-install', ['on'=> $server])
    echo " --- Running npm install... ----- ";
    cd {{ $laraveldirectory }}
    yarn install
    echo " --- npm install... complete for {{ $server }}";
@endtask

@task('build', ['on'=> $server])
    echo " --- Running npm run production ... ----- ";
    cd {{ $laraveldirectory }}
    npm run production
    echo " --- npm build... complete for {{ $server }}";
@endtask

@task('notify', ['on'=> $server])
       echo "Notify to slack"
@endtask

@finished
    if ($server != 'startmycompany.com.au')
        @slack('https://hooks.slack.com/services/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', '#production_deployments', "Deployment completed for $server ");
   
@endfinished


@task('app-down', ['on'=> $server])
    echo " --- shutting app down ----- ";
    cd {{ $laraveldirectory }}
    php artisan up 
    
@endtask

@task('app-up', ['on'=> $server])
    echo " --- starting app up ----- ";
    cd {{ $laraveldirectory }}
    php artisan up
    
@endtask

@error
    throw new Exception("Something went wrong");
@enderror