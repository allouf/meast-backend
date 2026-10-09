<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeLayers extends Command
{
    protected $signature = 'make:layers {module} {name}';
    protected $description = 'Generate a Repository Interface, Eloquent Repository, Service  inside a module, and bind them in AppServiceProvider';

    public function handle()
    {
        $module = $this->argument('module');
        $baseName = $this->argument('name');

        $modelName = $baseName;
        $modelVar = lcfirst($modelName);

        $className = "{$modelName}Repository";
        $interfaceName = "{$className}Interface";
        $repoVar = lcfirst($className);
        $serviceName = "{$modelName}Service";

        $modulePath = base_path("Modules/{$module}/app");
        // Paths
        $interfaceDir = "{$modulePath}/Repositories/Interfaces";
        $repositoryDir = "{$modulePath}/Repositories/Eloquent";
        $serviceDir = "{$modulePath}/Services";

        // $providerPath = app_path('Providers/AppServiceProvider.php');

        $interfacePath = "{$interfaceDir}/{$interfaceName}.php";
        $repositoryPath = "{$repositoryDir}/{$className}.php";
        $servicePath = "{$serviceDir}/{$serviceName}.php";

        // Ensure directories exist
        File::ensureDirectoryExists($interfaceDir);
        File::ensureDirectoryExists($repositoryDir);
        File::ensureDirectoryExists($serviceDir);

        // Create Interface
        if (!File::exists($interfacePath)) {

            File::put($interfacePath, $this->buildStub('repository-interface.stub', compact('module', 'interfaceName', 'modelName', 'modelVar')));
            $this->info("Created Interface: {$interfacePath}");
        } else {
            $this->warn("Interface already exists: {$interfacePath}");
        }

        // Create Repository
        if (!File::exists($repositoryPath)) {
            File::put($repositoryPath, $this->buildStub('repository-eloquent.stub', compact('module', 'className', 'interfaceName', 'modelName', 'modelVar')));
            $this->info("Created Repository: {$repositoryPath}");
        } else {
            $this->warn("Repository already exists: {$repositoryPath}");
        }

        // Create Service
        if (!File::exists($servicePath)) {
            File::put($servicePath, $this->buildStub('service.stub', compact('module', 'serviceName', 'interfaceName', 'repoVar', 'modelVar')));
            $this->info("Created Service: {$servicePath}");
        } else {
            $this->warn("Service already exists: {$servicePath}");
        }

        // Bind in AppServiceProvider
        $this->bindInServiceProvider($module, $interfaceName, $className);
        $this->call('optimize:clear');
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

    protected function bindInServiceProvider($module, $interfaceName, $className)
    {
        $providerPath = base_path("Modules/{$module}/app/Providers/{$module}ServiceProvider.php");

        if (!File::exists($providerPath)) {
            $this->error("Provider not found: {$providerPath}");
            return;
        }

        $interfaceFQN = "Modules\\{$module}\\Repositories\\Interfaces\\{$interfaceName}";
        $repositoryFQN = "Modules\\{$module}\\Repositories\\Eloquent\\{$className}";


        $useInterface = "use {$interfaceFQN};";
        $useRepository = "use {$repositoryFQN};";

        $bindingLine = "\$this->app->bind({$interfaceName}::class, {$className}::class);";

        $content = File::get($providerPath);

        // Add use statements if missing
        if (!str_contains($content, $useInterface)) {
            $content = preg_replace(
                '/namespace Modules\\\\' . $module . '\\\\Providers;/',
                "namespace Modules\\{$module}\\Providers;\n\n{$useInterface}",
                $content,
                1
            );
        }

        if (!str_contains($content, $useRepository)) {
            $content = preg_replace(
                '/' . preg_quote($useInterface, '/') . '/',
                "{$useInterface}\n{$useRepository}",
                $content,
                1
            );
        }

        // Inject the binding into the register() method
        if (!str_contains($content, $bindingLine)) {
            $content = preg_replace_callback(
                '/public function register\(\): void\s*\{\s*/',
                function ($matches) use ($bindingLine) {
                    return $matches[0] . "\n        {$bindingLine}\n";
                },
                $content
            );
        }

        File::put($providerPath, $content);

        $this->info("Clean binding added to {$module}ServiceProvider.");
    }
}
