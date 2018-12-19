<?hh // strict
namespace HHx\Util;
use HHx\ShareIterator;
function share<T>(AsyncIterator<T> $P): ShareIterator<T> {
	return new ShareIterator($P);
}