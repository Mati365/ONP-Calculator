<?php
/**
 * Funkcje będą deklarowane za pomocą
 * interfejsów
 */
abstract class Func {
	private $label;
	private $arg_count;

	public function Func($_label, $_arg_count) {
		$this->label = $_label;
		$this->arg_count = $_arg_count;
	}

	public function getArgCount() {
		return $this->arg_count;
	}

	public function getLabel() {
		return $this->label;
	}

	abstract public function calc($args);
}

/**
 * @author mati
 * Funkcje!
 */
class Sin extends Func {
	public function Sin() {
		parent::Func("sin", 1);
	}

	public function calc($args) {
		return sin($args[0]);
	}
}

class Cos extends Func {
	public function Cos() {
		parent::Func("cos", 1);
	}

	public function calc($args) {
		return cos($args[0]);
	}
}

class Atan extends Func {
	public function Atan() {
		parent::Func("atan", 1);
	}

	public function calc($args) {
		return atan($args[0]);
	}
}

class Tan extends Func {
	public function Tan() {
		parent::Func("tan", 1);
	}

	public function calc($args) {
		return tan($args[0]);
	}
}

class Ctg extends Func {
	public function Ctg() {
		parent::Func("ctg", 1);
	}

	public function calc($args) {
		return ctg($args[0]);
	}
}
?>