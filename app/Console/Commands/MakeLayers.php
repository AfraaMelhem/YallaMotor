<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeLayers extends Command
{
    protected $signature = 'make:layers {name}';
    protected $description = 'Generate a Repository Interface, Eloquent Repository, Service, and bind them in AppServiceProvider';

    public function handle()
    {
        $baseName = $this->argument('name');

        $modelName = $baseName;
        $modelVar = lcfirst($modelName);

        $className = "{$modelName}Repository";
        $interfaceName = "{$className}Interface";
        $interfaceVar = lcfirst($interfaceName);

        $serviceName = "{$modelName}Service";

        $storeRequestName = "Store{$modelName}Request";
        $updateRequestName = "Update{$modelName}Request";

        // Paths
        $interfaceDir = app_path('Repositories/Interfaces');
        $repositoryDir = app_path('Repositories/Eloquent');
        $providerPath = app_path('Providers/AppServiceProvider.php');

        $serviceDir = app_path('Services');

        $interfacePath = "{$interfaceDir}/{$interfaceName}.php";
        $repositoryPath = "{$repositoryDir}/{$className}.php";
        $servicePath = "{$serviceDir}/{$serviceName}.php";

        $requestDir = app_path("Http/Requests/{$modelName}");
        $storeRequestPath = "{$requestDir}/{$storeRequestName}.php";
        $updateRequestPath = "{$requestDir}/{$updateRequestName}.php";

        // Ensure directories exist
        File::ensureDirectoryExists($interfaceDir);
        File::ensureDirectoryExists($repositoryDir);
        File::ensureDirectoryExists($serviceDir);
        File::ensureDirectoryExists($requestDir);

        // Create Requests
        if (!File::exists($storeRequestPath)) {
//            dd($storeRequestName, $modelName, $modelVar);
            File::put($storeRequestPath, $this->buildStub('request-store.stub', compact('storeRequestName', 'modelName', 'modelVar')));
            $this->info("Created Request: {$storeRequestPath}");
        } else {
            $this->warn("Request already exists: {$storeRequestPath}");
        }

        if (!File::exists($updateRequestPath)) {
            File::put($updateRequestPath, $this->buildStub('request-update.stub', compact('updateRequestName', 'modelName', 'modelVar')));
            $this->info("Created Request: {$updateRequestPath}");
        } else {
            $this->warn("Request already exists: {$updateRequestPath}");
        }

        // Create Interface
        if (!File::exists($interfacePath)) {
            File::put($interfacePath, $this->buildStub('repository-interface.stub', compact('interfaceName')));
            $this->info("Created Interface: {$interfacePath}");
        } else {
            $this->warn("Interface already exists: {$interfacePath}");
        }

        // Create BaseRepository
        if (!File::exists($repositoryPath)) {
            File::put($repositoryPath, $this->buildStub('repository-eloquent.stub', compact('className', 'interfaceName', 'modelName', 'modelVar')));
            $this->info("Created Repository: {$repositoryPath}");
        } else {
            $this->warn("Repository already exists: {$repositoryPath}");
        }

        // Create Service
        if (!File::exists($servicePath)) {
            File::put($servicePath, $this->buildStub('service.stub', compact('serviceName', 'interfaceName', 'interfaceVar')));
            $this->info("Created Service: {$servicePath}");
        } else {
            $this->warn("Service already exists: {$servicePath}");
        }

        // Bind in AppServiceProvider
        $this->bindInServiceProvider($providerPath, $interfaceName, $className);
        $this->call('optimize:clear');

        // Create Requests Folder

    }

    protected function buildStub($stubName, array $replacements)
    {
        $stubPath = base_path("stubs/{$stubName}");
        $stub = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $stub = str_replace("{{{$key}}}", $value, $stub);
        }

        return $stub;
    }

    protected function bindInServiceProvider($providerPath, $interfaceName, $className)
    {
        $interfaceFQN = "App\\Repositories\\Interfaces\\{$interfaceName}";
        $repositoryFQN = "App\\Repositories\\Eloquent\\{$className}";

        $useInterface = "use {$interfaceFQN};";
        $useRepository = "use {$repositoryFQN};";

        $bindingLine = "\$this->app->bind({$interfaceName}::class, {$className}::class);";

        $content = File::get($providerPath);

        // Add use statements if missing
        if (!str_contains($content, $useInterface)) {
            $content = preg_replace('/namespace App\\\Providers;/', "namespace App\Providers;\n\n{$useInterface}", $content, 1);
        }

        if (!str_contains($content, $useRepository)) {
            $content = preg_replace('/' . preg_quote($useInterface, '/') . '/', "{$useInterface}\n{$useRepository}", $content, 1);
        }

        // Inject the binding into the register() method
        if (!str_contains($content, $bindingLine)) {
            $content = preg_replace_callback('/public function register\(\): void\s*\{\s*/', function ($matches) use ($bindingLine) {
                return $matches[0] . "\n        {$bindingLine}\n";
            }, $content);
        }

        File::put($providerPath, $content);

        $this->info("Clean binding added to AppServiceProvider.");
    }


}
