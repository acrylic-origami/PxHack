<?hh // strict
use Px\{NullablePointer};
use namespace HH\Asio;
// see https://stackoverflow.com/questions/47946750/
<<__Entrypoint>>
function Q47946750(): void {
	$last = new NullablePointer();
	$delay = 0.1;
	$source = async {
		for($i = 0;; $i++) {
			$_last = $last->get();
			$now = microtime(true);
			if($_last === null) {
				await Asio\usleep(intval($delay * 1E6));
			}
			else {
				await Asio\usleep(intval(
					max(0, ($delay - ($now - $_last - $delay)) * 1E6)
				));
			}
			yield $i;
			$last->set($now);
		}
	};
	Asio\join(async {
		foreach($source await as $v) {
			echo microtime(true) . "\n";
		}
	});
}