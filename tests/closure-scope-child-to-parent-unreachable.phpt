--TEST--
Test that closure scope is restored when copying closures from dead child to parent thread via an object unreachable from the thread at join time
--DESCRIPTION--
Thread::join() copies closures reachable from the thread object into the parent before the child thread exits.
A closure stored in an object that is no longer reachable from the thread at join time is only copied when the
parent first reads it, after the child thread's memory, including the closure's scope class, has been freed.
--XFAIL--
Closures copied after the child thread exits are not handled yet (dac0cdf: "problems may still occur due to copying occurring too late")
--XLEAK--
Closures copied after the child thread exits read freed memory (dac0cdf: "problems may still occur due to copying occurring too late")
--FILE--
<?php

$a = new \pmmp\thread\ThreadSafeArray();
$q = new \pmmp\thread\ThreadSafeArray();
$q[] = $a;

$t = new class($q) extends \pmmp\thread\Thread{
	public function __construct(private \pmmp\thread\ThreadSafeArray $q){}

	public function run() : void{
		require __DIR__ . '/assets/ExternalClosureDefinitionChildToParent.php';

		$a = $this->q->shift();
		$a['closure'] = ExternalClosureDefinitionChildToParent::getClosure();
	}
};

$t->start(\pmmp\thread\Thread::INHERIT_ALL) && $t->join();
($a['closure'])();
echo "OK\n";
?>
--EXPECT--
string(38) "ExternalClosureDefinitionChildToParent"
string(38) "ExternalClosureDefinitionChildToParent"
OK
