<?php

use Curl\Curl;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;

class GetForkRepos extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $curl = new Curl;
        $repos = App\Repository::where('fork', true)->where('parent_id', null)->get();
        $count = count($repos);
        $c_init = $count;
        foreach ($repos as $repo) {
            echo "Remaining repos: ".($count)."\n";
            $count--;
            echo "Requesting child ".$repo->full_name."...\n";
            $curl->get("https://api.github.com/repos/".$repo->full_name."?client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792");
            echo "done!\n";
            $resp = json_decode($curl->response);

            if(isset($resp->block)){
                echo "Block repository. Removing from DB\n";
                $repo->delete();
                continue;
            }
            if(($r = App\Repository::find($resp->parent->id)) != null){
                echo "Parent already on db\n";
                $repo->parent_id = $r->id;
                $repo->save();
                continue;
            }

            echo "Requesting parent...\n";
            $curl->get("https://api.github.com/repos/".$resp->parent->full_name."?client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792");
            echo "done!\n";
            $resp = json_decode($curl->response);

            $remaining_reqs = intval(explode(": ", $curl->response_headers[7])[1]);

            echo $remaining_reqs."\n";

            try{
                $user = new App\User;
                $user->id = $resp->owner->id;
                $user->login = $resp->owner->login;
                $user->avatar_url = $resp->owner->avatar_url;
                $user->url = $resp->owner->html_url;
                $user->name = "010temp";
                $user->save();
            }catch(QueryException $e){
                echo "Same user for repo".$resp->full_name."\n";
            }

            $r = new App\Repository;
            $r->id = $resp->id;
            $r->name = $resp->name;
            $r->full_name = $resp->full_name;
            $r->description = $resp->description;
            $r->fork = $resp->fork;
            $r->url = $resp->html_url;
            $r->master_branch = "master";
            $r->owner_id = $resp->owner->id;
            $r->save();

            $repo->parent_id = $resp->id;
            $repo->save();

            if($remaining_reqs < 2){
                echo "Not enough requests\n";
                echo "Retry at :".date('l jS \of F Y h:i:s A', intval(explode(": ", $curl->response_headers[8])));
                break;
            }
        }
    }
}
