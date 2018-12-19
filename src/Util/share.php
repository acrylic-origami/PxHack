<?hh // strict
namespace HPx\Util;
use HPx\ShareIterator;
function share<T>(AsyncIterator<T> $P): ShareIterator<T> {
	return new ShareIterator($P);
}