<?php
namespace App\Services;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class FplClient {
 private function http(): PendingRequest { return Http::baseUrl(config('services.fpl.url'))->acceptJson()->timeout(20)->retry(3,500); }
 public function bootstrap(): array { return $this->http()->get('/bootstrap-static/')->throw()->json(); }
 public function standings(int $leagueId): array {
  $page=1;$all=[];$league=null;
  do {$data=$this->http()->get("/leagues-classic/$leagueId/standings/",['page_standings'=>$page])->throw()->json();$league??=$data['league']??[];$all=array_merge($all,$data['standings']['results']??[]);$more=$data['standings']['has_next']??false;$page++;} while($more&&$page<20);
  return ['league'=>$league,'results'=>$all];
 }
 public function entry(int $id): array {return $this->http()->get("/entry/$id/")->throw()->json();}
 public function picks(int $id,int $gw): array {return $this->http()->get("/entry/$id/event/$gw/picks/")->throw()->json();}
 public function history(int $id): array {return $this->http()->get("/entry/$id/history/")->throw()->json();}
 public function live(int $gw): array {return $this->http()->get("/event/$gw/live/")->throw()->json();}
}
