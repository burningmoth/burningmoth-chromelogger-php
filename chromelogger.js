{

	/**
	 * Remove __proto__ and length properties from objects and arrays.
	 * @since 1.6
	 * @param mixed obj
	 * @return mixed
	 */
	cleanObjectProperties: function( obj ) {

		// not an object ? return as-is ...
		if ( typeof obj !== 'object' ) return obj;
		
		var 
		// namespace object back reference ...
		ns = this,
		
		// clone object / original object needs to remain as-is / removes length property from arrays ...
		tmp = Object.assign({}, obj);

		// remove annoying __proto__ property ...
		tmp.__proto__ = null;

		// recurse through properties ...
		Object.entries(tmp).forEach(([ key, value ])=>{ tmp[ key ] = ns.cleanObjectProperties( value ); });

		// return cloned object ...
		return tmp;

	},

};


/**
 * Custom callstack method / always called deferred ...
 */
console.callstack = function( label, msg ) { this.log( label, msg ); };