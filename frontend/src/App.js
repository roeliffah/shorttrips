import React from "react";

function App() {
  return (
    <div style={{ width: "100vw", height: "100vh", margin: 0, padding: 0, overflow: "hidden" }}>
      <iframe
        src="https://shorttrips.eu/"
        title="Shorttrips WordPress Frontend"
        style={{ width: "100vw", height: "100vh", border: "none", margin: 0, padding: 0, display: "block" }}
        allowFullScreen
      />
    </div>
  );
}

export default App;
