  </div> <!-- End of profile-grid -->
</div> <!-- End of layout -->

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var preview = document.getElementById('avatarPreview');
      if (preview.tagName === 'IMG') {
        preview.src = e.target.result;
      } else {
        var newImg = document.createElement('img');
        newImg.id = 'avatarPreview';
        newImg.src = e.target.result;
        newImg.style.cssText = preview.style.cssText;
        preview.parentNode.replaceChild(newImg, preview);
      }
      
      // Update top header avatar instantly as well
      document.querySelectorAll('.avatar-img').forEach(function(el) {
        if(el.tagName === 'IMG') el.src = e.target.result;
      });
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function toggleMasterNotif(masterCheckbox) {
  const isChecked = masterCheckbox.checked;
  const childCheckboxes = document.querySelectorAll('.child-notif');
  childCheckboxes.forEach(cb => {
    cb.checked = isChecked;
    cb.disabled = !isChecked;
    if(!isChecked) {
       cb.nextElementSibling.style.opacity = '0.5';
       cb.nextElementSibling.style.cursor = 'not-allowed';
    } else {
       cb.nextElementSibling.style.opacity = '1';
       cb.nextElementSibling.style.cursor = 'pointer';
    }
  });
  showSaveToast(isChecked ? 'All notifications enabled' : 'All notifications disabled');
}

let toastTimeout;
function showSaveToast(message) {
  const toast = document.getElementById('settingsToast');
  if (!toast) return;
  
  if (message) toast.textContent = message;
  
  toast.classList.add('show');
  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toast.classList.remove('show');
  }, 3000);
}
</script>
</body>
</html>
