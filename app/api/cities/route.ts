import { NextRequest, NextResponse } from 'next/server';
import { parseStringPromise } from 'xml2js';

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const region_id = searchParams.get('region_id');
  const username = process.env.SUNHOTELS_USER!;
  const password = process.env.SUNHOTELS_PASS!;
  const soapBody = `
    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
      <soap:Body>
        <GetCitiesV2 xmlns="http://xml.sunhotels.net/15/">
          <userName>${username}</userName>
          <password>${password}</password>
          <resortId>${region_id}</resortId>
          <language>en</language>
        </GetCitiesV2>
      </soap:Body>
    </soap:Envelope>
  `;
  const response = await fetch("http://xml.sunhotels.net/15/SOAP/NonStaticXMLAPI.asmx", {
    method: "POST",
    headers: {
      "Content-Type": "text/xml; charset=utf-8",
      "SOAPAction": "http://xml.sunhotels.net/15/GetCitiesV2"
    },
    body: soapBody
  });
  const xml = await response.text();
  const json = await parseStringPromise(xml, { explicitArray: false });
  const cities = json["soap:Envelope"]["soap:Body"]["GetCitiesV2Response"]["GetCitiesV2Result"]["Cities"]["City"];
  const results = Array.isArray(cities)
    ? cities.map((c: any) => ({ id: c.CityId, name: c.Name }))
    : [];
  return NextResponse.json({ results });
}