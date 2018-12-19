<?hh // strict
namespace HPx\Source;
use namespace HH\Asio;

async function interval(int $delay): AsyncIterator<int> {
	for($i = 0; ; $i++) {
		await Asio\usleep($delay);
		yield $i;
	}
}