<?php

namespace EasyPack\Console\Commands\Scaffolding;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:oxygen:crud {name : The name of the entity}
                            {--api : Generate API controller}
                            {--admin : Generate admin controller}
                            {--model : Generate model}
                            {--repository : Generate repository}
                            {--migration : Generate migration}
                            {--views : Generate views}
                            {--routes : Add routes}
                            {--all : Generate everything}
                            {--force : Overwrite files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate complete CRUD scaffolding (model, repository, controllers, views, routes)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = Str::studly(Str::singular($this->argument('name')));
        $all = $this->option('all');

        $this->info("Generating CRUD scaffolding for: {$name}");
        $this->newLine();

        $generated = [];

        // Generate Model
        if ($all || $this->option('model')) {
            $this->call('make:oxygen:model', ['name' => $name]);
            $generated[] = 'Model';
        }

        // Generate Repository
        if ($all || $this->option('repository')) {
            $this->call('make:oxygen:repository', ['name' => $name]);
            $generated[] = 'Repository';
        }

        // Generate Migration
        if ($all || $this->option('migration')) {
            $tableName = Str::snake(Str::plural($name));
            $this->call('make:migration', [
                'name' => "create_{$tableName}_table",
                '--create' => $tableName,
            ]);
            $generated[] = 'Migration';
        }

        // Generate API Controller
        if ($all || $this->option('api')) {
            $this->call('make:oxygen:api-controller', ['name' => $name]);
            $generated[] = 'API Controller';
        }

        // Generate Admin Controller
        if ($all || $this->option('admin')) {
            $this->call('make:oxygen:admin-controller', ['name' => $name]);
            $generated[] = 'Admin Controller';
        }

        // Generate Views
        if ($all || $this->option('views')) {
            $this->generateViews($name);
            $generated[] = 'Views';
        }

        // Add Routes
        if ($all || $this->option('routes')) {
            $this->showRouteInstructions($name);
            $generated[] = 'Routes (see instructions)';
        }

        $this->newLine();
        $this->info('✓ Generated: ' . implode(', ', $generated));
        $this->newLine();

        // Show next steps
        $this->showNextSteps($name);

        return Command::SUCCESS;
    }

    /**
     * Generate Blade views for the entity.
     */
    protected function generateViews(string $name): void
    {
        $viewPath = resource_path('views/manage/' . Str::kebab(Str::plural($name)));

        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        $entityLower = strtolower($name);
        $entityPlural = Str::plural($entityLower);
        $entityPluralStudly = Str::studly(Str::plural($name));
        $entityTitle = Str::headline($name);
        $entityPluralTitle = Str::headline(Str::plural($name));
        $routePrefix = 'manage.' . Str::kebab(Str::plural($name));

        // Index View
        $indexContent = $this->getIndexViewStub($entityLower, $entityPlural, $entityPluralStudly, $entityTitle, $entityPluralTitle, $routePrefix);
        file_put_contents("{$viewPath}/index.blade.php", $indexContent);
        $this->line("  Created: views/manage/" . Str::kebab(Str::plural($name)) . "/index.blade.php");

        // Create View
        $createContent = $this->getCreateViewStub($entityLower, $entityPlural, $entityTitle, $routePrefix);
        file_put_contents("{$viewPath}/create.blade.php", $createContent);
        $this->line("  Created: views/manage/" . Str::kebab(Str::plural($name)) . "/create.blade.php");

        // Edit View
        $editContent = $this->getEditViewStub($entityLower, $entityPlural, $entityTitle, $routePrefix);
        file_put_contents("{$viewPath}/edit.blade.php", $editContent);
        $this->line("  Created: views/manage/" . Str::kebab(Str::plural($name)) . "/edit.blade.php");

        // Show View
        $showContent = $this->getShowViewStub($entityLower, $entityPlural, $entityTitle, $routePrefix);
        file_put_contents("{$viewPath}/show.blade.php", $showContent);
        $this->line("  Created: views/manage/" . Str::kebab(Str::plural($name)) . "/show.blade.php");
    }

    /**
     * Show route instructions.
     */
    protected function showRouteInstructions(string $name): void
    {
        $entityPlural = Str::kebab(Str::plural($name));
        $controllerName = Str::studly(Str::plural($name)) . 'Controller';
        $apiControllerName = Str::studly(Str::plural($name)) . 'Controller';

        $this->line('  Add these routes to your routes files:');
        $this->newLine();
        $this->line("  // Web Routes (routes/web.php)");
        $this->line("  Route::resource('{$entityPlural}', \\App\\Http\\Controllers\\Manage\\{$controllerName}::class);");
        $this->newLine();
        $this->line("  // API Routes (routes/api.php)");
        $this->line("  Route::apiResource('{$entityPlural}', \\App\\Http\\Controllers\\Api\\V1\\{$apiControllerName}::class);");
    }

    /**
     * Show next steps after generation.
     */
    protected function showNextSteps(string $name): void
    {
        $this->info('Next Steps:');
        $this->line('1. Run: php artisan migrate');
        $this->line('2. Update the generated model with fillable fields');
        $this->line('3. Update the generated views with your form fields');
        $this->line('4. Add navigation item in your AppServiceProvider:');
        $this->newLine();
        $this->line("   Navigator::addItem([");
        $this->line("       'text' => '" . Str::headline(Str::plural($name)) . "',");
        $this->line("       'icon_class' => 'fas fa-list',");
        $this->line("       'resource' => 'manage." . Str::kebab(Str::plural($name)) . ".index',");
        $this->line("       'permission' => 'view-" . Str::kebab(Str::plural($name)) . "',");
        $this->line("       'order' => 50,");
        $this->line("   ], 'sidebar');");
    }

    protected function getIndexViewStub(string $entityLower, string $entityPlural, string $entityPluralStudly, string $entityTitle, string $entityPluralTitle, string $routePrefix): string
    {
        return <<<BLADE
@extends('layouts.app')

@section('title', '{$entityPluralTitle}')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{$entityPluralTitle}</h1>
        </div>
        <a href="{{ route('{$routePrefix}.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add {$entityTitle}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if(\${$entityPlural}->isEmpty())
                <div class="text-center py-5">
                    <h5 class="text-muted">No {$entityPluralTitle} Found</h5>
                    <a href="{{ route('{$routePrefix}.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i> Add {$entityTitle}
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\${$entityPlural} as \${$entityLower})
                                <tr>
                                    <td>{{ \${$entityLower}->id }}</td>
                                    <td>{{ \${$entityLower}->name ?? 'N/A' }}</td>
                                    <td>{{ \${$entityLower}->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('{$routePrefix}.edit', \${$entityLower}) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="{{ route('{$routePrefix}.destroy', \${$entityLower}) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ \${$entityPlural}->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
BLADE;
    }

    protected function getCreateViewStub(string $entityLower, string $entityPlural, string $entityTitle, string $routePrefix): string
    {
        return <<<BLADE
@extends('layouts.app')

@section('title', 'Create {$entityTitle}')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Create {$entityTitle}</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('{$routePrefix}.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ \$message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="{{ route('{$routePrefix}.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
BLADE;
    }

    protected function getEditViewStub(string $entityLower, string $entityPlural, string $entityTitle, string $routePrefix): string
    {
        return <<<BLADE
@extends('layouts.app')

@section('title', 'Edit {$entityTitle}')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Edit {$entityTitle}</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('{$routePrefix}.update', \${$entityLower}) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', \${$entityLower}->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ \$message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('{$routePrefix}.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
BLADE;
    }

    protected function getShowViewStub(string $entityLower, string $entityPlural, string $entityTitle, string $routePrefix): string
    {
        return <<<BLADE
@extends('layouts.app')

@section('title', '{$entityTitle} Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{$entityTitle} Details</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('{$routePrefix}.edit', \${$entityLower}) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('{$routePrefix}.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ \${$entityLower}->id }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ \${$entityLower}->name ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ \${$entityLower}->created_at->format('F d, Y H:i') }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
BLADE;
    }
}
