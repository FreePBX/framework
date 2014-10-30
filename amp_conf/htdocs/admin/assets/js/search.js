// Javascript handler 
 
var searchLookup = {};

var globalSearch = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  limit: 10,
  prefetch: {
    ttl: 1000,
    url: 'ajax.php?module=search&command=global&t=9',
    filter: function(data) { console.log(data); return $.map(data, function(t) { searchLookup[t.text] = t; return { value: t.text } }); },
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
    name: 'globalSearch',
    displayKey: 'value',
    source: globalSearch.ttAdapter()
  });
});
