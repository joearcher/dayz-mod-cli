<?php

namespace App\Commands;

use Illuminate\Support\Str;
use function Laravel\Prompts\text;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\multiselect;
use LaravelZero\Framework\Commands\Command;

class Create extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the base folder and file structure for a new DayZ mod.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mod_name = text(
            label: 'What is the new mod called?',
            placeholder: 'My_test_mod',
            hint: 'This will be used for the base class names and folder paths.',
            required: true,
        );

        $mod_name = Str::replace(' ', '_', $mod_name);
        $mod_name = Str::replace('\'', '', $mod_name);
        $mod_name = Str::replace('"', '', $mod_name);
        $mod_name = Str::replace('`', '', $mod_name);


        $scriptModules = multiselect(
            label: 'Which script modules should be enabled for ' . $mod_name . '?',
            options: ['3_Game', '4_World', '5_Mission']
        );

        Storage::makeDirectory($mod_name);
        Storage::makeDirectory($mod_name . '/Scripts');

        $relativePath = '/stubs/config.cpp.stub';

        $stubPath =  file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__ . $relativePath;

        $stub = file_get_contents($stubPath);

        if (in_array('3_Game', $scriptModules)) {
            $gameString = $this->getGameModuleString();
            $gameDep = '"Game",';
            $stub = str_replace('{ gamescript module }', $gameString, $stub);
            $stub = str_replace('{ game dep }', $gameDep, $stub);
            Storage::makeDirectory($mod_name . '/Scripts/3_Game');
        } else {
            $stub = str_replace('{ gamescript module }', '', $stub);
            $stub = str_replace('{ game dep }', '', $stub);
        }

        if (in_array('4_World', $scriptModules)) {
            $worldString = $this->handleWorld();
            $worldDep = '"World",';
            $stub = str_replace('{ worldscript module }', $worldString, $stub);
            $stub = str_replace('{ world dep }', $worldDep, $stub);
            Storage::makeDirectory($mod_name . '/Scripts/4_World');
        } else {
            $stub = str_replace('{ worldscript module }', '', $stub);
            $stub = str_replace('{ world dep }', '', $stub);
        }

        if (in_array('5_Mission', $scriptModules)) {
            $missionString = $this->handleMission();
            $missionDep = '"Mission",';
            $stub = str_replace('{ missionscript module }', $missionString, $stub);
            $stub = str_replace('{ mission dep }', $missionDep, $stub);
            Storage::makeDirectory($mod_name . '/Scripts/5_Mission');
        } else {
            $stub = str_replace('{ missionscript module }', '', $stub);
            $stub = str_replace('{ mission dep }', '', $stub);
        }

        $output = str_replace(['{ mod_name }'], $mod_name, $stub);






        File::put($mod_name . '/config.cpp', $output);
        $this->info('Mod folder structure created successfully.');
    }

    private function getGameModuleString()
    {
        return 'class gameScriptModule
            {
                value="";
                files[]=
                    {
                        "{ mod_name }/Scripts/3_Game"
                    };
            };';
    }
    private function handleWorld()
    {
        return 'class worldScriptModule
            {
                value="";
                files[]=
                    {
                        "{ mod_name }/Scripts/4_World"
                    };
            };';
    }
    private function handleMission()
    {
        return 'class missionScriptModule
            {
                value="";
                files[]=
                    {
                        "{ mod_name }/Scripts/5_Mission"
                    };
            };';
    }
}
