import { NextRequest, NextResponse } from 'next/server';
import { parseStringPromise } from 'xml2js';

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const query = searchParams.get('q');
  const username = process.env.SUNHOTELS_USER!;
  const password = process.env.SUNHOTELS_PASS!;
  const soapBody = `
    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
      <soap:Body>
        <GetDestinationsV2 xmlns="http://xml.sunhotels.net/15/">
          <userName>${username}</userName>
          <password>${password}</password>
          <language>en</language>
        </GetDestinationsV2>
      </soap:Body>
    </soap:Envelope>
  `;
  const response = await fetch("http://xml.sunhotels.net/15/SOAP/NonStaticXMLAPI.asmx", {
    method: "POST",
    headers: {
      "Content-Type": "text/xml; charset=utf-8",
      "SOAPAction": "http://xml.sunhotels.net/15/GetDestinationsV2"
    },
    body: soapBody
  });
  const xml = await response.text();
  const json = await parseStringPromise(xml, { explicitArray: false });
  const result = json?.["soap:Envelope"]?.["soap:Body"]?.["GetDestinationsV2Response"]?.["GetDestinationsV2Result"];
  if (!result || !result.Destinations) {
    return NextResponse.json({ results: [] });
  }
  const destinations = result.Destinations.Destination;
  const all = Array.isArray(destinations) ? destinations : [destinations];
  // Filter op naam (land, regio, stad, hotelnaam)
  const filtered = all.filter((d: any) =>
    d.Name?.toLowerCase().includes(query?.toLowerCase() || "")
  );
  return NextResponse.json({ results: filtered });
}