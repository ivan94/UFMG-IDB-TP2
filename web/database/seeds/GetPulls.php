<?php

use Curl\Curl;
use App\Repository;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;

class GetPulls extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $curl = new Curl;
        $repositories = DB::select("SELECT * FROM repositories WHERE id NOT IN (SELECT repository_id FROM pulls UNION SELECT r_id FROM mark_repos)");
        $remaining_reqs = 5000;
        for ($i=0; $i < count($repositories); $i++) {
            $repository = $repositories[$i];
            echo ((floatval($i)/count($repositories))*100)."%\n";
            $page = 1;
            do{
                $url = "https://api.github.com/repos/$repository->full_name/pulls?state=all&page=$page&per_page=100&client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792";
                echo "Requesting $url\n";
                $curl->get($url);
                echo "done!\n";
                if($curl->http_status_code == 301){
                    $url = explode(": ", $curl->response_headers[9])[1];
                    $curl->get($url);
                }
                if($curl->http_status_code == 204){
                    break;
                }
                $response = json_decode($curl->response);
                foreach ($response as $pull) {
                    try{
                        $user = new App\User;
                        $user->id = $pull->user->id;
                        $user->login = $pull->user->login;
                        $user->avatar_url = $pull->user->avatar_url;
                        $user->url = $pull->user->html_url;
                        $user->name = "010temp";
                        $user->save();
                    }catch(QueryException $e){
                        echo "User $user->login already registered\n";
                    }
                    DB::table('pulls')->insert([
                        'user_id'       => $pull->user->id,
                        'repository_id' => $repository->id,
                        'number'        => $pull->number,
                        'title'         => $pull->title,
                        'state'         => $pull->state,
                        'locked'        => $pull->locked,
                        'url'           => $pull->html_url,
                        ]);
                }
                $page++;
                $remaining_reqs = intval(explode(": ", $curl->response_headers[7])[1]);
                if($remaining_reqs < 2){
                    break;
                }
            }while(count($response) == 100);

            DB::table('mark_repos')->insert(['r_id' => $repository->id]);
            if($remaining_reqs < 2){
                echo "Not enough requests\n";
                echo "Retry at :".date('l jS \of F Y h:i:s A', intval(explode(": ", $curl->response_headers[8])[1]));
                break;
            }
        }
    }
}
