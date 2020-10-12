Helsingborg = Helsingborg || {};
Helsingborg.ExampleNamespace = Helsingborg.Liquid || {};

Helsingborg.ExampleNamespace.ExampleClass = (function ($) {

	var classVariable = false;

    /**
     * Constructor
     * Should be named as the class itself
     */
	function ExampleClass() {

    }

    /**
     * Method
     */
    ExampleClass.prototype.exampleMethod = function () {

    }

	return new ExampleClass();

})(jQuery);
