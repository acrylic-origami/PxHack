<?hh // strict
namespace HHx;
use namespace HH\Asio;
use namespace HH\Lib\{C, Vec};
use HH\Asio\Scheduler as S;

use function HHx\Util\_elementwise;

function flat_map<Tu, Tv>((function(Tu): Supplier<Tv>) $f): Operator<Tu, Tv> {
	$halt = new NullablePointer();
	// somewhere here there needs to be a launch. Not sure where.
	return $up ==> async $down ==> {
		$_down = async $w ==> { $r = await $down($w); $halt->set($r); };
		await $up(async $v ==> {
			if($halt->get() !== null)
				return true;
			
			await $f($v)($_down);
		});
	};
}
function debounce<T>(int $delay): Operator<T, T> {
	$idx = new Pointer(0);
	$halt = new NullablePointer();
	return _elementwise(async ($v, $down) ==> {
		$stash = $idx->get();
		$idx->set(($idx->get() ?? 0) + 1);
		S::launch(async {
			await \HH\Asio\usleep($delay);
			if($idx === $stash) {
				await $halt->aset($down($v));
			}
		});
		return $halt->get();
	});
}

function first<T>(): Operator<T, T> {
	return _elementwise(async ($v, $down) ==> {
		await $down($v);
		return false;
	});
}

function group_by<Tk as arraykey, T>((function(T): Tk) $keyfn): Operator<T, Supplier<T>> {
	$D = dict[]; // Dict<Tk, Consumer<T>>
	return _elementwise<T, Supplier<T>>(async ($v, $down) ==> {
		$k = $keyfn($v);
		$has_channel = C\contains_key($D, $k);
		if(!$has_channel) {
			// a unique instance where we need a state machine in case the inner streams are subscribed later or not at all
			$inner = new NullablePointer(); // Pointer<Consumer<Supplier<T>>>
			$D[$k] = $v ==> ($inner->get() ?? fun('anop'))($v);
			await $down(async $_inner ==> $inner->set($_inner));
		}
		return await $D[$k]($v);
	});
}