// Javascript handler 
 
var globalSearch = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  remote: {
    url: 'ajax.php?module=search&command=global',
    filter: function(list) { console.log(list);
      return $.map(list, function(x) { console.log(x); return { name: x.text }; }); 
    }
  }
});

globalSearch.initialize();


$(document).ready(function() {
  console.log("herex");
  $('#fpbxsearch .typeahead').typeahead({
    hint: true,
    highlight: true,
    minLength: 1
  }, {
    name: 'states',
    displayKey: 'name',
    source: globalSearch.ttAdapter()
  });
});
