// FreePBX Search
//
var SearchC = Class.extend({
  init: function() {
    // initialize
    var self = this;
    this.moduleSearch = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 10,
      prefetch: {
        ttl: 1000,
        url: 'ajax.php?module=search&command=global&t=9',
       filter: function(data) { console.log(data); return $.map(data, function(t) { return { value: t.text, o: t } }); },
      }
    });
    this.moduleSearch.initialize();
    $('#fpbxsearch .typeahead').typeahead({
      hint: true,
      highlight: true,
      minLength: 1
    }, {
      name: 'moduleSearch',
      displayKey: 'value',
      source: this.moduleSearch.ttAdapter()
    })
    .bind("typeahead:selected", function(o,d,n) { self.processSearchClick(o,d,n); });
  },
  processSearchClick: function(o, d, name) {
    if (typeof(d.o.type) == "undefined") {
      console.log("Madness.", d);
      return false;
    }
    if (d.o.type == "get") {
      window.location.search = d.o.dest;
      return true;
    }
    console.log("No idea what to do with this: ", d);
  },
});

$(document).ready(function() {
  var Search = new SearchC();
});
