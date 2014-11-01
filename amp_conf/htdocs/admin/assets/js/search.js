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
       filter: function(data) { return $.map(data, function(t) { return { value: t.text, o: t } }); },
      }
    });
    this.moduleSearch.initialize();

    this.itemSearch = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 10,
      remote: {
        ttl: 100,
        url: 'ajax.php?module=search&command=local&query=%QUERY&section='+window.modulename,
        filter: function(data) { console.log(data); return $.map(data, function(t) { return { raw: true, value: t.text, o: t } }); },
      },
    });
    this.itemSearch.initialize();

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
    }, {
      name: 'itemSearch',
      displayKey: 'value',
      source: this.itemSearch.ttAdapter(),
      templates: {
        suggestion: self.genItemHtml,
      }
    })
    .bind("typeahead:selected", function(o,d,n) { self.processSearchClick(o,d,n); })
    .focus();
  },

  extMatch: function(strs) {
    var self = this;
    return function findMatches(q, cb) {
      var matches, substrRegex;
      matches = [];
      substrRegex = new RegExp(q, 'i');
      $.each(strs, function(i, str) {
        if (substrRegex.test(str)) {
	  // We're an Extension!
	  if (self.extLookup[str] == str) {
	    // It's an extension
            matches.push({ value: "Extension "+str, ext: str });
	  } else {
	    // It's a name
            matches.push({ value: str+" ("+self.extLookup[str]+")", ext: str });
	  }
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
  genItemHtml: function(o) {
    if (o.o.type == "text") {
      return "<div>"+o.value+"</div>";
    } else {
      return "<div class='tt-suggestion'><p>"+o.value+"</p></div>";
    }
  },
});

$(document).ready(function() {
  window.Search = new SearchC();
});
