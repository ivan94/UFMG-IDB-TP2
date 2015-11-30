<?php
use Curl\Curl;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;

class RepoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $since = rand(0, 46735000);
        $curl = new Curl;
        $j = 50;
        do{
            echo (((50-$j)/50.0)*100)."%\n";
            echo "Requesting...\n";
            $curl->get("https://api.github.com/repositories?since=$since&client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792");
            echo "done! since = $since\n";
            $resp = json_decode($curl->response);
            for ($i=0; $i < count($resp); $i++) {
                try{
                    $user = new App\User;
                    $user->id = $resp[$i]->owner->id;
                    $user->login = $resp[$i]->owner->login;
                    $user->avatar_url = $resp[$i]->owner->avatar_url;
                    $user->url = $resp[$i]->owner->html_url;
                    $user->name = "010temp";
                    $user->save();
                }catch(QueryException $e){
                    echo "Same user for repo".$resp[$i]->full_name."\n";
                }
                // try{
                    $repo = new App\Repository;
                    $repo->id = $resp[$i]->id;
                    $repo->name = $resp[$i]->name;
                    $repo->full_name = $resp[$i]->full_name;
                    $repo->description = $resp[$i]->description;
                    $repo->url = $resp[$i]->html_url;
                    $repo->owner_id = $resp[$i]->owner->id;
                    $repo->save();
                // }catch(QueryException $e){
                //     echo "Getting same repos, skiping.\n";
                // }
            }

            $since = rand(0, 46735000);
            $j--;
        }while($j>0);
    }
}
