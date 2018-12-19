<?hh // strict
require_once(__DIR__ . '/../vendor/hh_autoload.php');

use Px\{Pointer};
use namespace HH\Asio;
use HH\Asio\Scheduler as S;
use function Px\{group_by};
use function Px\Util\{share, P2S};
use function Px\Source\interval;

// see https://stackoverflow.com/questions/46879555/
<<__Entrypoint>>
function Q46879555(): void {
	$S = P2S((interval(intval(100E3))));
	
	$latest = Vector{};
	$idx = new Pointer(-1);
	
	$operate = $down ==> group_by($v ==> $v % 3)($S)($S ==> {
		$idx->set($idx->get() + 1);
		$_idx = $idx->get();
		$latest->add(null);
		return $S(async $v ==> {
			$latest[$_idx] = $v;
			await $down($latest->toImmVector());
		});
	});
	
	Asio\join(Asio\v(vec[
		S::run(),
		$operate(async $v ==> { var_dump($v); })
	]));
}