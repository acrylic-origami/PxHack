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
			
			S::launch($f($v)($_down));
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
	$D = Map{}; // Map<Tk, Consumer<T>>
	return _elementwise<T, Supplier<T>>(async ($v, $down) ==> {
		$k = $keyfn($v);
		$has_channel = $D->contains($k);
		if(!$has_channel) {
			// a unique instance where we need a state machine in case the inner streams are subscribed later or not at all
			$inner = new NullablePointer(); // Pointer<Consumer<Supplier<T>>>
			$D[$k] = $v ==> ($inner->get() ?? fun('anop'))($v);
			await $down(async $_inner ==> $inner->set($_inner));
		}
		return await $D[$k]($v);
	});
}

function publish<T>(Supplier<T> $up): Supplier<T> {
	$downs = Vector{};
	$cancels = Vector{};
	$acks = new Pointer(0);
	$lifetime = $up(async $v ==> {
		$F_cancels = vec[];
		for($i = 0; $i < $downs->count(); $i++) {
			if($cancels[$i]->get() === null) {
				$F_cancels[] = async {
					$cancel = await $downs[$i]($v);
					$cancels[$i]->set($cancel);
					return $cancel;
				};
			}
		}
		$new_cancels = await Asio\v($F_cancels);
		if(!C\any($new_cancels, $v ==> $v === null))
			return CANCEL;
	});
	return $down ==> {
		$downs->add($down);
		$cancels->add(new NullablePointer());
		return $lifetime;
	};
}

function fork<T>(Consumer<T> ...$downs): Hole<T> {
	return $up ==> {
		$O = publish($up);
		$lifetime = null;
		foreach($downs as $down)
			$lifetime = $O($down);
		return $lifetime ?? async{};
	};
}

function replay<T>(Supplier<T> $up): Supplier<T> {
	$O = publish($up);
	$buffer = Vector{};
	S::launch($O(async $v ==> { $buffer->add($v); }));
	return async $down ==> {
		for($i = 0; $i < $buffer->count(); $i++) { // every `foreach` should have an `await`, else it should be `for`
			await $down($buffer[$i]);
		}
		await $O($down); // no race condition should be possible so long as this always gets tacked onto the END of the subscriber list in `publish`
	};
}