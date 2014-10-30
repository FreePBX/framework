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
      name: 'extenSearch',
      displayKey: 'value',
      source: this.extMatch(this.getAllExtens()),
    }, {
      name: 'moduleSearch',
      displayKey: 'value',
      source: this.moduleSearch.ttAdapter()
    })
    .bind("typeahead:selected", function(o,d,n) { self.processSearchClick(o,d,n); })
    .focus();
  },

  extMatch: function(strs) {
    return function findMatches(q, cb) {
      var matches, substrRegex;
      matches = [];
      substrRegex = new RegExp(q, 'i');
      $.each(strs, function(i, str) {
        if (substrRegex.test(str)) {
          matches.push({ value: "Extension "+str, ext: str });
        }
      });
      cb(matches);
    };
  },

  getAllExtens: function() {
    var self = this;
    self.extLookup = {};
    var knownExtensions = [];
    $.each(extmap, function(x) { 
      if (this.match(/User Exten/)) { 
        knownExtensions.push(x); 
	self.extLookup[x] = x;
	var extName = this.toString().replace("User Extension: ", "");
        knownExtensions.push(extName); 
	self.extLookup[extName] = x;
      }
    });
    return knownExtensions;
  },
      
  processSearchClick: function(o, d, name) {
    console.log(d);
    if (name == "moduleSearch") {
      return this.processModuleClick(d.o);
    } else if (name == "extenSearch") {
      return this.processExtenClick(d);
    } else {
      console.log("All the wat "+name);
      return false;
    }
  },
  processModuleClick: function(o) {
    if (o.type == "get") {
      window.location.search = o.dest;
      return true;
    }
    console.log("No idea what to do with this: ", o);
  },
  processExtenClick: function(o) {
    var ext = this.extLookup[o.ext];
    window.location.search = "?display=extensions&extdisplay="+ext;
    window.location.hash = "";
    return true;
  },
});

$(document).ready(function() {
  var Search = new SearchC();
});
