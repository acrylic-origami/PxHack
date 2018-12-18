<?hh // strict
namespace HHx\Util;
use HHx\{Supplier, const CANCEL};

function P2S<T>(AsyncIterator<T> $P): Supplier<T> {
	return async $C ==> {
		foreach($P await as $v) {
			$cancel = await $C($v);
			if($cancel !== null)
				return CANCEL;
		}
	};
}