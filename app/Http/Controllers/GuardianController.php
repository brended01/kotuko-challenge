<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Bhaktaraz\RSSGenerator\Item;
use Bhaktaraz\RSSGenerator\Feed;
use Bhaktaraz\RSSGenerator\Channel;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{
    /**
     * Returns a page formatted in rss depending on the search.
     *
     * @param [string] $category
     * @return void
     */
    public function index($category){
        $err = "";
        if($this->checkKebabCase($category)){
            if($response = $this->checkCaching($category)){
                return $response;
            }else{
                if($response = $this->getGuardianCategory($category)){
                    $rssGenerated = $this->generateRSS($response,$category);
                    if($this->storeRss($category,$rssGenerated)){
                        return $rssGenerated;
                    }else{
                        $err = "Failed caching";
                    }
                }else{
                    $err = "There is no result";
                }
            }
        }else{
            $err = "The search must be done with the kebab case convention";
        }
        return $err;
    }


    /**
     * Stores data for caching in the database
     *
     * @param [string] $tag
     * @param [Feed] $rss
     * @return bool
     */
    public function storeRss($tag,$rss){

        if(DB::table('caching')
            ->insert(['tag' => $tag,
                      'rss' => $rss,
                      'expiryDate' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
                     ])){
            
            return true;
        }
        return false;
    }

    /**
     * Check if the requested category is already present in the database and if it has not expired
     *
     * @param [string] $category
     * @return void
     */
    public function checkCaching($category){
        date_default_timezone_set('Europe/Rome');

            $dbResponse = DB::table('caching')->where('tag',$category)->get();
            if(!empty($dbResponse)){
                
                if($dbResponse[0]->expiryDate > date('Y-m-d H:i:s')){
                    return $dbResponse[0]->rss;
                }else{
                    DB::table('caching')->where('tag',$category)->delete();
                    return false;
                }
            }

        else{
            return false;
        }
    }


    /**
     * Generates with the result of the request to the guardian, an object formatted according to the rss convention
     *
     * @param [array] $response
     * @param [string] $category
     * @return void
     */
    public function generateRSS($response,$category){
        $feed = new Feed();
        
        $channel = new Channel();
        $channel
            ->title("Result of: ".$category)
            ->description("Laravel Kotuko Challenge")
            ->url('http://localhost:8070/api/'.$category)
            ->appendTo($feed);

        foreach ($response["response"]["results"] as $article){
            $item = new Item();
            $item
                ->title($article['webTitle'])
                ->description('Type: '.$article['type'])
                ->pubDate(strtotime($article['webPublicationDate']))
                ->url($article['webUrl'])
                ->category($article['sectionName'])
                ->appendTo($channel);
        }


        return $feed;
    }

    /**
     * Check if the search was done using the kebabcase notation
     *
     * @param [string] $category
     * @return bool
     */
    public function checkKebabCase($category){
        if($category[0] == '-' || $category[strlen($category)-1] == '-')
        {
            return false;
        }
            
        for ($i = 0; strlen($category)-1 >= $i; $i++){
            if(($category[$i] < 'a' || $category[$i] > 'z') && $category[$i] != '-'){
                return false;
            }
                
        }
        return true;
    }

    /**
     * Richiesta api a the guardian con la categoria cercata
     *
     * @param [string] $category
     * @return array
     */
    public function getGuardianCategory($category){
        return Http::withHeaders([
            'api-key' => '61bd1f09-3ef5-40d6-b1dc-0d2ab0305ff4',
        ])->get('https://content.guardianapis.com/search', [
            'section' => $category,
        ]);
    }
}
