<?php

namespace Azuriom\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ThemeCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:create
                        {name : The name of the theme}
                        {id? : The id of the theme}
                        {--no-config : Disable config creation}
                        {--author=Azuriom : The author of the theme}
                        {--description=Theme for Azuriom : The description of the theme}
                        {--url=https://azuriom.com : The url of the theme}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Azuriom theme';

    protected Filesystem $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $id = $this->argument('id') ?? Str::slug($name);
        $path = themes_path($id);

        if ($this->files->exists($path)) {
            $this->error('The theme '.$path.' already exists!');

            return 1;
        }

        $this->files->makeDirectory($path);

        $this->createThemeJson($path, $id, $name);

        if (! $this->hasArgument('no-config')) {
            $this->createConfigJson($path);
        }

        $this->files->makeDirectory($path.'/assets');
        $this->files->makeDirectory($path.'/views');

        $this->info('Theme created successfully.');

        return 0;
    }

    private function createThemeJson(string $path, string $id, string $name): void
    {
        $this->files->put($path.'/theme.json', json_encode([
            'id' => $id,
            'name' => $name,
            'description' => $this->option('description'),
            'version' => '1.0.0',
            'url' => $this->option('url'),
            'authors' => [
                $this->option('author'),
            ],
            'azuriom_api' => '1.1.0',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function createConfigJson(string $path): void
    {
        $this->files->put($path.'/config.json', '{}');
    }
}
