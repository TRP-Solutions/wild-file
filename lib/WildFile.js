/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/

var WildFile = (function(){
	function checksum(field) {
		let fieldname = field.name;
		if(fieldname.indexOf("[")!=-1) {
			fieldname = fieldname.slice(0,fieldname.indexOf("["));
		}
		document.querySelectorAll('[name^="'+fieldname+'_checksum"]').forEach(function (e) {e.remove()});
		for(const file of field.files) {
			const fileReader = new FileReader();
			fileReader.readAsArrayBuffer(file);
			fileReader.onload = async function(entry) {
				const digest = await crypto.subtle.digest('SHA-256', entry.target.result);
				const hash = Array.from(new Uint8Array(digest)).map(x => x.toString(16).padStart(2, '0')).join('');
				const input = document.createElement("input");
				input.setAttribute("name", fieldname+"_checksum["+file.name+"]");
				input.setAttribute("type", "hidden2");
				input.setAttribute("value", hash);
				field.after(input);
			}
		}
	}

	var exportobj = {
		checksum: checksum,
	};

	return exportobj;
})();
