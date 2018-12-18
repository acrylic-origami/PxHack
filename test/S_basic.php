<?hh // strict
require_once(__DIR__ . '/../vendor/hh_autoload.php');
use namespace HH\Asio;
use namespace HH\Lib\{C, Vec};

use HHx\{S, Pointer, NullablePointer, AsyncCondition};
use namespace HHx\Util;
use HHx\{Consumer};

function S_basic(): void {
	$sources = Vec\map(Vec\range(0, 5), async $v ==> {
		for($i = 0; ; $i++) {
			await Asio\usleep(intval(mt_rand(0, 1E6)));
			yield Pair{ $v, $i };
		}
	}) // Vec<Producer>
		|> Vec\map($$, fun('HHx\Util\P2S')); // Vec<Supplier>
		
	// $all = $C ==> Asio\v(Vec\map($sources, $C)); // implicit merge
	// $gate = debounce(100)($all);
	// Asio\join(
	// 	window($gate)($all)
	// 	|> flat_map($S ==> {
	// 		$P = async { while(true) { await Asio\usleep(intval(100E3)); yield null; } };
	// 		$inS = P2S($P);
	// 		$i = new Pointer(false);
	// 		return async $downstream ==> {
	// 			S::launch(window($inS)(async $_ ==> { $i->set(true); return true; })($S)); // note this launch.
	// 			await $inS(async $v ==> {
	// 				if($i->get() === true) await $downstream(null);
	// 				return true;
	// 			});
	// 		};
	// 	})($$)(async $v ==> { echo 'TRIG'; return true; }) // i.e. buffer + filter
	// );
}

// merge is trivial