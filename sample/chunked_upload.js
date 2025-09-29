/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/

function file_add(entry){
	var progress = document.createElement('progress');
	progress.value = 0;

	var li = document.createElement('li');
	li.dataset.fileId = entry.id;
	li.append(entry.chunked_file.file.name+' ', progress);

	var ul = document.querySelector('#chunked_upload_files');
	ul.append(li);

	entry.progressElement = progress;
}

function file_upload(entry){
	entry.progressElement.removeAttribute('value');
}

function file_progress(entry, value, max){
	entry.progressElement.value = value;
	entry.progressElement.max = max;
}

function file_complete(entry){
	entry.progressElement.replaceWith("\u2705");
	delete(entry.progressElement);
}

WildFile.Filelist.get("upload123").options({
	onadd: file_add,
	onupload: file_upload,
	onprogress: file_progress,
	oncomplete: file_complete,
});
