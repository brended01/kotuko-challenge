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
    public function index($category){
        if($this->checkKebabCase($category)){
            if($response = $this->checkCaching($category)){
                return $response;
            }else{
                if($response = $this->getGuardianCategory($category)){
                    $rssGenerated = $this->generateRSS($response,$category);
                    if($this->storeRss($category,$rssGenerated)){
                        return $rssGenerated;
                    }else{
                        return "Failed caching";
                    }
                }else{
                    return "There is no result";
                }
            }
        }else{
            return "Not well formed";
        }
    }

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


    public function checkCaching($category){
        date_default_timezone_set('Europe/Rome');

            $dbResponse = DB::table('caching')->where('tag',$category)->get();
            if(sizeof($dbResponse) != 0){
                
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


    public function checkKebabCase($category){
        if($category[0] == '-' || $category[strlen($category)-1] == '-')
            return false;
        for ($i = 0; strlen($category)-1 >= $i; $i++){
            if(($category[$i] < 'a' || $category[$i] > 'z') && $category[$i] != '-')
                return false;
        }
        return true;
    }

    public function getGuardianCategory($category){
        $response = Http::withHeaders([
            'api-key' => '61bd1f09-3ef5-40d6-b1dc-0d2ab0305ff4',
        ])->get('https://content.guardianapis.com/search', [
            'section' => $category,
        ]);
        return $response;
    }
}
