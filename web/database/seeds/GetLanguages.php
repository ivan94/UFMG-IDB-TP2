<?php

use Curl\Curl;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;

class GetLanguages extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $curl = new Curl;
        $repositories = DB::select("SELECT * FROM repositories WHERE id NOT IN (SELECT repository_id FROM languages)");
        $remaining_reqs = 5000;
        for ($i=0; $i < count($repositories); $i++) {
            $repository = $repositories[$i];
            echo ((floatval($i)/count($repositories))*100)."%\n";
            $page = 1;
            do{
                $url = "https://api.github.com/repos/".$repository->full_name."/languages?page=$page&per_page=100&client_id=3389257578926deac1d1&client_secret=3930b2c6f0cf91c3e2b83873db15cc26368fe792";
                echo "Requesting $url\n";
                $curl->get($url);
                echo "done!\n";
                if($curl->http_status_code != 200){
                    echo "Status code $curl->http_status_code. Breaking...\n";
                    break;
                }
                $response = json_decode($curl->response, true);
                foreach ($response as $language => $ocurrences) {
                    try{
                        DB::table('languages')->insert([
                            'language'      => $language,
                            'repository_id' => $repository->id,
                            ]);
                    }catch(QueryException $e){
                        echo "Language with the same name $Language \n";
                    }
                }
                $page++;
                $remaining_reqs = intval(explode(": ", $curl->response_headers[7])[1]);
                if($remaining_reqs < 2){
                    break;
                }
            }while(count($response) == 100);

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
