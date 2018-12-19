<?hh // strict
namespace HHx\Util;
use HHx\{Supplier, const CANCEL};

function P2S<T>(AsyncIterator<T> $P): Supplier<T> {
	$S = share($P); // with other sharing ops (like publish) opinionating with `share` here is reasonable (and probably best)
	return async $C ==> {
		foreach($S await as $v) {
			$cancel = await $C($v);
			if($cancel !== null)
				return CANCEL;
		}
	};
}