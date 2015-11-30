<?php

use Curl\Curl;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;

class GetCommits extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
     public function run()
     {
         $curl = new Curl;
         $branches = DB::select("SELECT b.*, r.full_name FROM branches b, repositories r WHERE r.id = b.repository_id AND (b.repository_id, b.name) NOT IN (select repository_id, branch_name from commits)");
         $remaining_reqs = 5000;
         for ($i=0; $i < count($branches); $i++) {
             $branch = $branches[$i];
             echo ((floatval($i)/count($branches))*100)."%\n";
             $page = 1;
             do{
                 $url = "https://api.github.com/repos/".$branch->full_name."/commits?sha=$branch->name&page=$page&per_page=100&client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792";
                 echo "Requesting $url\n";
                 $curl->get($url);
                 echo "done!\n";
                 if($curl->http_status_code == 204){
                     break;
                 }
                 $response = json_decode($curl->response);
                 //var_dump($response);
                 foreach ($response as $commit) {
                    //  try{
                    $user_id = null;
                    if($commit->author != null){
                        try{
                            $user = new App\User;
                            $user->id = $commit->author->id;
                            $user->login = $commit->author->login;
                            $user->avatar_url = $commit->author->avatar_url;
                            $user->url = $commit->author->html_url;
                            $user->name = "010temp";
                            $user->save();
                        }catch(QueryException $e){
                            echo "User $user->login already registered\n";
                        }
                        $user_id = $commit->author->id;
                    }
                    try{
                    DB::table('commits')->insert([
                        'sha'           => $commit->sha,
                        'message'       => $commit->commit->message,
                        'url'           => $commit->html_url,
                        'user_id'       => $user_id,
                        'repository_id' => $branch->repository_id,
                        'branch_name'   => $branch->name,
                        ]);
                    }catch(QueryException $e){
                        echo "Commit $commit->sha already registered\n";
                    }
                    //  }catch(QueryException $e){
                    //      echo "Commit with the same sha $commit->sha \n";
                    //  }
                 }
                 $page++;
                 $remaining_reqs = intval(explode(": ", $curl->response_headers[7])[1]);
                 if($remaining_reqs < 2){
                     break;
                 }
             }while(count($response) == 100);

             if($remaining_reqs < 2){
                 echo "Not enough requests\n";
                 echo "Retry at :".date('l jS \of F Y h:i:s A', intval(explode(": ", $curl->response_headers[8])[1]));
                 break;
             }
         }
     }
}
