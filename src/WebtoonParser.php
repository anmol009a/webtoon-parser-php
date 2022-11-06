<?php

namespace WebtoonParser;

use Parsehub\Parsehub;

class Project
{
    public  $token;
    public  $title;
    public  $last_ready_run_token;

    function __construct(string $token, string $title, $last_ready_run)
    {
        $this->token = $token;
        $this->title = $title;
        $this->last_ready_run_token = $last_ready_run;
    }
}

class Webtoon
{
    function __construct(string $title, string $url, string $cover_url, array|null $chapters = [])
    {
        $this->id = null;
        $this->title = $title;
        $this->url = $url;
        $this->cover_url = $cover_url;
        $this->chapters = $chapters;
    }
}

class Chapter
{
    public $number;
    public $url;

    function __construct(string $number, string $url)
    {
        $this->number = $number;
        $this->url = $url;
    }
}

class WebtoonParser
{
    /**
     * Constructor - takes your ParseHub API key
     */
    function __construct(string $api_key)
    {
        $this->parsehub = new Parsehub($api_key);
    }

    /**
     * @return array of objects
     */
    function get_all_webtoon_projects()
    {
        $projectList = [];
        // $webtoonProjects = json_decode($this->parsehub->getProjectList());
        $webtoonProjects = $this->parsehub->getProjectList()->projects;
        // $webtoonProjects = json_encode($this->parsehub->getProjectList()->projects);
        foreach ($webtoonProjects as $project) {
            $a = new Project($project->token, $project->title, isset($project->last_ready_run->run_token) ? $project->last_ready_run->run_token : null);
            array_push($projectList, $a);
        };

        // echo json_encode($projectList);
        return $projectList;
    }

    /**
     * @return array of started run run_tokens
     */
    function run_all_webtoon_projects()
    {
        $runList = [];
        $projectList = $this->get_all_webtoon_projects();

        foreach ($projectList as $project) {
            if ($project->last_ready_run_token === null) {
                $run_obj = $this->parsehub->runProject($project->token);
                array_push($runList, $run_obj->run_token);
            }
        }

        return $runList;
    }

    /**
     * @return array of deleted run_tokens
     */
    function delete_all_webtoon_run()
    {
        $deleteList = [];

        $projectList = $this->get_all_webtoon_projects();

        foreach ($projectList as $project) {
            if ($project->last_ready_run_token !== null) {
                $delete_obj = $this->parsehub->deleteProjectRun($project->last_ready_run_token);
                array_push($deleteList, $delete_obj->run_token);
            }
        }

        return $deleteList;
    }

    /**
     * Get webtoons from all runs
     * @return array of Webtoon Class objects
     */
    function get_all_webtoon_run_data()
    {
        $webtoon_data = []; // variable to store webtoons
        $projectList = $this->get_all_webtoon_projects();   // get projectList

        // for each project
        foreach ($projectList as $project) {
            // check if last_ready_run is null
            if ($project->last_ready_run_token !== null) {

                // get run data
                $run_data = $this->parsehub->getRunData($project->last_ready_run_token);

                // from json to stdClass object
                $run_data = json_decode($run_data)->webtoons;

                // put each webtoon object in $webtoon_data
                foreach ($run_data as $webtoon) {

                    if (!isset($webtoon->chapters))
                        $webtoon->chapters = [];
                        
                    // define new webtoon object
                    $webtoon = new Webtoon($webtoon->title, $webtoon->url, $webtoon->cover_url, $webtoon->chapters);

                    //push object into array
                    array_push($webtoon_data, $webtoon);
                }
            }
        }

        // return an array of Webtoon Class Objects
        return $webtoon_data;
    }
}
