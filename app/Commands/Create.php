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


    public string $mod_name;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->mod_name = text(
            label: 'What is the new mod called?',
            placeholder: 'My_test_mod',
            hint: 'This will be used for the base class names and folder paths.',
            required: true,
        );

        $this->mod_name = Str::replace(' ', '_', $this->mod_name);
        $this->mod_name = Str::replace('\'', '', $this->mod_name);
        $this->mod_name = Str::replace('"', '', $this->mod_name);
        $this->mod_name = Str::replace('`', '', $this->mod_name);

        if (Storage::exists($this->mod_name)) {
            $this->error('A folder with this mod name already exists in the current path, please choose a different name.');
            return 1;
        }


        $scriptModules = multiselect(
            label: 'Which script modules should be enabled for ' . $this->mod_name . '?',
            options: ['3_Game', '4_World', '5_Mission']
        );

        Storage::makeDirectory($this->mod_name);
        Storage::makeDirectory($this->mod_name . '/Scripts');

        $relativePath = '/stubs/config.cpp.stub';

        $stubPath =  file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__ . $relativePath;

        $stub = file_get_contents($stubPath);


        $stub = $this->handleGameScriptModule($stub, $scriptModules);
        $stub = $this->handleWorldScriptModule($stub, $scriptModules);
        $stub = $this->handleMissionScriptModule($stub, $scriptModules);

        $output = str_replace(['{ mod_name }'], $this->mod_name, $stub);

        File::put($this->mod_name . '/config.cpp', $output);
        $this->info('Mod folder structure created successfully.');
    }

    /**
     * Handle creation of elements for game script module
     * @param string $stub string of config.cpp stub
     * @param array $scriptModules array of script module choices
     * @return string
     */
    private function handleGameScriptModule(string $stub, array $scriptModules): string
    {
        if (!in_array('3_Game', $scriptModules)) {
            $stub = str_replace('{ gamescript module }', '', $stub);
            $stub = str_replace('{ game dep }', '', $stub);
            return $stub;
        }

        $gameString = $this->getGameModuleString();
        $gameDep = '"Game",';
        $stub = str_replace('{ gamescript module }', $gameString, $stub);
        $stub = str_replace('{ game dep }', $gameDep, $stub);
        Storage::makeDirectory($this->mod_name . '/Scripts/3_Game');

        return $stub;
    }

    /**
     * Handle creation of elements for world script module
     * @param string $stub string of config.cpp stub
     * @param array $scriptModules array of script module choices
     * @return string
     */
    private function handleWorldScriptModule(string $stub, array $scriptModules): string
    {
        if (!in_array('4_World', $scriptModules)) {
            $stub = str_replace('{ worldscript module }', '', $stub);
            $stub = str_replace('{ world dep }', '', $stub);
            return $stub;
        }

        $worldString = $this->getWorldModuleString();
        $worldDep = '"World",';
        $stub = str_replace('{ worldscript module }', $worldString, $stub);
        $stub = str_replace('{ world dep }', $worldDep, $stub);
        Storage::makeDirectory($this->mod_name . '/Scripts/4_World');

        return $stub;
    }

    /**
     * Handle creation of elements for mission script module
     * @param string $stub string of config.cpp stub
     * @param array $scriptModules array of script module choices
     * @return string
     */
    private function handleMissionScriptModule(string $stub, array $scriptModules): string
    {
        if (!in_array('5_Mission', $scriptModules)) {
            $stub = str_replace('{ missionscript module }', '', $stub);
            $stub = str_replace('{ mission dep }', '', $stub);
            return $stub;
        }
        $missionString = $this->getMissionModuleString();
        $missionDep = '"Mission",';
        $stub = str_replace('{ missionscript module }', $missionString, $stub);
        $stub = str_replace('{ mission dep }', $missionDep, $stub);
        Storage::makeDirectory($this->mod_name . '/Scripts/5_Mission');

        return $stub;
    }


    /**
     * Get the string for the game script module class block
     * @return string
     */
    private function getGameModuleString(): string
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

    /**
     * Get the string for the world script module class block
     * @return string
     */
    private function getWorldModuleString()
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

    /**
     * Get the string for the mission script module class block
     * @return string
     */
    private function getMissionModuleString()
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
