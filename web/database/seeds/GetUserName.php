<?php

use Curl\Curl;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;

class GetUserName extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $curl = new Curl;
        $users = App\User::where('name', '010temp')->get();
        $count = count($users);
        foreach ($users as $user) {
            echo "Remaining users: ".($count)."\n";
            $count--;

            echo "Requesting user ".$user->login."...\n";
            $curl->get("https://api.github.com/users/".$user->login."?client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792");
            echo "done!\n";
            $resp = json_decode($curl->response);

            if(isset($resp->block)){
                echo "Blocked user. Removing from DB\n";
                $user->delete();
                continue;
            }

            if($curl->http_status_code == 404){
                echo "Deleting user and repos";
                DB::table('branches')->join('repositories', 'repository_id', '=', 'id')->where('owner_id', $user->id)->delete();
                DB::table('contributes_to')->join('repositories', 'repository_id', '=', 'id')->where('owner_id', $user->id)->orWhere('user_id', $user->id)->delete();
                DB::table('mark_repos')->join('repositories', 'r_id', '=', 'id')->where('owner_id', $user->id)->delete();
                DB::table('repositories')->where('owner_id', $user->id)->delete();
                $user->delete();
                continue;
            }

            $remaining_reqs = intval(explode(": ", $curl->response_headers[7])[1]);
            echo $remaining_reqs."\n";

            $user->name = $resp->name;
            $user->save();

            if($remaining_reqs < 2){
                $time = intval(explode(": ", $curl->response_headers[8])[1]);
                echo "Not enough requests\n";
                echo "Retry at :".date('l jS \of F Y h:i:s A', $time)."\n";
                echo "Sleeping...";
                sleep($time - time());
                echo "Awaking...\n";
            }
        }
    }
}
