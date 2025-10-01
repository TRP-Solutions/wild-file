/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/

var upload_handler = {
	add: function(entry){
		var progress = document.createElement('progress');
		progress.value = 0;

		var li = document.createElement('li');
		li.dataset.fileId = entry.id;
		li.append(entry.chunked_file.file.name+' ', progress);

		var ul = document.querySelector('#chunked_upload_files');
		ul.append(li);

		entry.progressElement = progress;
	},
	upload: function(entry){
		entry.progressElement.removeAttribute('value');
	},
	progress: function(entry, value, max){
		entry.progressElement.value = value;
		entry.progressElement.max = max;
	},
	complete: function(entry){
		entry.progressElement.replaceWith("\u2705");
		delete(entry.progressElement);
	},
	listfinish: function(filelist, successful_files, total_files){
		console.log('Uploaded %o out of %o files.\n%o', successful_files, total_files, filelist);
	}
}

WildFile.list("upload123").set_handler(upload_handler);
