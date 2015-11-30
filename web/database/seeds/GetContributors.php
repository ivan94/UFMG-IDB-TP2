<?php

use Curl\Curl;
use App\Repository;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;
//  use Symfony\Component\HttpFoundation\HeaderBag;

class GetContributors extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $curl = new Curl;
        $repositories = DB::select("SELECT * FROM repositories WHERE id NOT IN (SELECT repository_id FROM contributes_to)");
        $remaining_reqs = 5000;
        for ($i=0; $i < count($repositories); $i++) {
            $repository = $repositories[$i];
            echo ((floatval($i)/count($repositories))*100)."%\n";
            $page = 1;
            do{
                $url = "https://api.github.com/repos/".$repository->full_name."/contributors?page=$page&per_page=100&client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792";
                echo "Requesting $url\n";
                $curl->get($url);
                echo "done!\n";
                if($curl->http_status_code == 204){
                    break;
                }
                $response = json_decode($curl->response);
                //var_dump($response);
                foreach ($response as $contributor) {
                    try{
                        $user = new App\User;
                        $user->id = $contributor->id;
                        $user->login = $contributor->login;
                        $user->avatar_url = $contributor->avatar_url;
                        $user->url = $contributor->html_url;
                        $user->name = "010temp";
                        $user->save();
                    }catch(QueryException $e){
                        echo "User $user->login already registered\n";
                    }
                    DB::table('contributes_to')->insert([
                        'user_id'       => $contributor->id,
                        'repository_id' => $repository->id,
                        'contributions' => $contributor->contributions,
                        ]);
                }
                $page++;
                $remaining_reqs = intval(explode(": ", $curl->response_headers[7])[1]);
                if($remaining_reqs < 2){
                    break;
                }
            }while(count($response) == 100);

            if($remaining_reqs < 2){
                echo "Not enough requests\n";
                echo "Retry at :".date('l jS \of F Y h:i:s A', intval(explode(": ", $curl->response_headers[8])));
                break;
            }
        }
    }
}
