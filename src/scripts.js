(function(w){
	var editor;
	var inEditMode = false;

	function createEditor(id) {
		if (inEditMode) {
			return;
		}

		// Note: editor.getData() could be used if we want to save via an ajax request
		document.getElementById('easy-editing-textarea-' + id).value = document.getElementById('easy-editing-current-content-' + id).innerHTML;
		editor = CKEDITOR.inline('easy-editing-' + id);
		inEditMode = true;

		toggleEditControls(id, true);
	}

	function cancelEditor(id) {
		if (!inEditMode) {
			return;
		}

		toggleEditControls(id, false);

		editor.destroy();
		editor = null;
		inEditMode = false;
	}

	function saveNewClearanceLevel(id) {
		var status = document.getElementById("easy-editing-admin-status-" + id);
		var clearanceLevel = document.getElementById("easy-editing-admin-select-" + id).value;
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function () {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				status.innerHTML = "Saved.";
			}
		}
		status.innerHTML = "Saving...";
		xmlhttp.open("POST", window.location, true);
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlhttp.send("easy-editing-request=change-clearance-level&easy-editing-id=" + id + "&easy-editing-clearance-level=" + clearanceLevel);
	}

	function toggleEditControls(id, show) {
		document.getElementById('easy-editing-view-' + id).style.display = show ? 'none' : '';
		document.getElementById('easy-editing-form-' + id).style.display = show ? '' : 'none';
	}

	function toggleAdminModal(id, show) {
		toggleClass(document.getElementById('easy-editing-admin-modal-' + id), 'modal-dialog--show', show);
	}

	function toggleClass(element, cssClass, add) {
		var classes = element.className.split(/\s+/), a = [];
		for(var i = 0; i < classes.length; i++) {
			if(classes[i] != cssClass) {
				a.push(classes[i]);
			}
		}
		if(add) {
			a.push(cssClass);
		}
		element.className = a.join(' ');
	}

	w.easyEditing = {
		createEditor: createEditor,
		cancelEditor: cancelEditor,
		saveNewClearanceLevel: saveNewClearanceLevel,
		showAdminModal: function(id) {
			toggleAdminModal(id, true);
		},
		hideAdminModal: function(id) {
			toggleAdminModal(id, false);
		}
	};
})(window);