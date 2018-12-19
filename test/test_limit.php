<?hh // strict
use namespace HH\Asio;
use namespace HH\Lib\{C, Vec};
use HH\Asio\Scheduler as S;
use function Px\{debounce, flat_map, publish};
use function Px\Util\{share, P2S};

use Px\{Pointer, NullablePointer, SharableIterator};
use Px\{Consumer};

function test_limit(): void {
	$start = microtime(true);
	$sources = Vec\map(Vec\range(0, 5), async $v ==> {
		for($i = 0; ; $i++) {
			await Asio\usleep(intval(mt_rand(500E3, 1E6)));
			yield tuple($v, $i, microtime(true) - $start);
		}
	}) // Vec<Producer>
		|> Vec\map($$, fun('Px\Util\P2S')); // Vec<Supplier>
		
	$all = publish($C ==> Asio\v(Vec\map($sources, $source ==> $source($C)))); // implicit merge
	$gate = debounce(intval(800E3))($all);
	
	$limit = $down ==> {
		// window-like state machine
		$gates = new Pointer(1);
		S::launch($gate(async $v ==> {
			$gates->set($gates->get() + 1);
		}));
		
		// inner bufferTime/throttle state machine
		$latest = new NullablePointer();
		$intervals = new Pointer(0);
		return $all(async $v ==> {
			$latest->set($v);
			if($gates->get() > 0) {
				$intervals->set($intervals->get() + 1);
				$interval_stash = $intervals->get();
				$gates->set(0);
				
				S::launch(async { // interval
					while(true) {
						if($intervals->get() > $interval_stash || $gates->get() > 0)
							return;
						$_latest = $latest->get();
						invariant($latest !== null, "By order of setting this can't be null.");
						await $down($_latest);
						await Asio\usleep(intval(800E3));
					}
				});
			}
		});
	};
	S::launch($limit(async $v ==> var_dump($v)));
	
	Asio\join(Asio\v(vec[
		S::run()
	]));
		// window($gate)($all)
		// |> flat_map($S ==> {
		// 	$P = async { while(true) { await Asio\usleep(intval(100E3)); yield null; } };
		// 	$inS = share($P);
		// 	$i = new Pointer(false);
		// 	return async $downstream ==> {
		// 		S::launch(window($inS)(async $_ ==> { $i->set(true); return true; })($S)); // note this launch.
		// 		await $inS(async $v ==> {
		// 			if($i->get() === true) await $downstream(null);
		// 			return true;
		// 		});
		// 	};
		// })($$)(async $v ==> { echo 'TRIG'; return true; }) // i.e. buffer + filter
}