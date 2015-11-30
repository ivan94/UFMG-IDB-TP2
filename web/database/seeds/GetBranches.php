<?php

use Curl\Curl;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;

class GetBranches extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $curl = new Curl;
        $repositories = DB::select("SELECT * FROM repositories WHERE id NOT IN (SELECT repository_id FROM branches)");
        $remaining_reqs = 5000;
        for ($i=0; $i < count($repositories); $i++) {
            $repository = $repositories[$i];
            echo ((floatval($i)/count($repositories))*100)."%\n";
            $page = 1;
            do{
                $url = "https://api.github.com/repos/".$repository->full_name."/branches?page=$page&per_page=100&client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792";
                echo "Requesting $url\n";
                $curl->get($url);
                echo "done!\n";
                if($curl->http_status_code == 204){
                    break;
                }
                $response = json_decode($curl->response);
                //var_dump($response);
                foreach ($response as $branch) {
                    try{
                        DB::table('branches')->insert([
                            'name'       => $branch->name,
                            'repository_id' => $repository->id,
                            'url' => $repository->url."/tree/".$branch->name,
                            ]);
                    }catch(QueryException $e){
                        echo "Branch with the same name $branch->name \n";
                    }
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
