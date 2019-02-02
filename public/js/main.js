$('#search_form_price').on('change input', function() {
    document.getElementById('search_form_rangeValue').value=this.value;
}).change();