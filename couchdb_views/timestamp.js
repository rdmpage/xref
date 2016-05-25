function(doc) {
  if (doc.type) {
    if (doc.type == 'url') {
      if (!doc.content) {
        emit(doc.timestamp, doc.url);
      }
    }
  }
}